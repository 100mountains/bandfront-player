<?php
declare(strict_types=1);

namespace Bandfront\Audio;

use Bandfront\Core\Config;
use Bandfront\Storage\FileManager;
use Bandfront\Utils\Debug;

// Set domain for Audio
Debug::domain('audio');

/**
 * Audio Processing
 * 
 * Handles audio file processing, truncation, and demo creation
 * using FFmpeg or PHP fallbacks
 * 
 * @package Bandfront\Audio
 * @since 2.0.0
 */
class Processor {
    
    private Config $config;
    private FileManager $fileManager;
    
    /**
     * Constructor
     */
    public function __construct(Config $config, FileManager $fileManager) {
        $this->config = $config;
        $this->fileManager = $fileManager;
    }
    
    /**
     * Process secure audio with truncation
     * 
     * @param array $fileInfo File information
     * @param array $args Request arguments
     * @return bool Success status
     */
    public function processSecureAudio(array &$fileInfo, array $args): bool {
        $filePercent = intval($args['file_percent']);
        $ffmpeg = $this->config->getState('_bfp_ffmpeg');
        
        Debug::log('Processor: processSecureAudio started', ['filePercent' => $filePercent, 'ffmpeg_enabled' => $ffmpeg]); // DEBUG-REMOVE
        
        $processed = false;
        
        // Try FFmpeg first if available
        if ($ffmpeg && function_exists('shell_exec')) {
            Debug::log('Processor: trying FFmpeg'); // DEBUG-REMOVE
            $processed = $this->processWithFfmpeg($fileInfo['filePath'], $fileInfo['oFilePath'], $filePercent);
            Debug::log('Processor: FFmpeg result', $processed); // DEBUG-REMOVE
        }
        
        // Fall back to PHP processing
        if (!$processed) {
            Debug::log('Processor: trying PHP fallback'); // DEBUG-REMOVE
            $processed = $this->processWithPhp($fileInfo['filePath'], $fileInfo['oFilePath'], $filePercent);
            Debug::log('Processor: PHP result', $processed); // DEBUG-REMOVE
        }
        
        // Swap files if processing succeeded
        if ($processed && file_exists($fileInfo['oFilePath'])) {
            Debug::log('Processor: swapping files'); // DEBUG-REMOVE
            $this->swapProcessedFile($fileInfo);
        }
        
        do_action('bfp_truncated_file', $args['product_id'], $args['url'], $fileInfo['filePath']);
        
        return $processed;
    }
    
    /**
     * Process audio file with FFmpeg
     * 
     * @param string $inputPath Input file path
     * @param string $outputPath Output file path
     * @param int $filePercent Percentage to keep
     * @return bool Success status
     */
    private function processWithFfmpeg(string $inputPath, string $outputPath, int $filePercent): bool {
        $settings = $this->config->getStates([
            '_bfp_ffmpeg_path',
            '_bfp_ffmpeg_watermark'
        ]);
        
        $ffmpegPath = $this->prepareFfmpegPath($settings['_bfp_ffmpeg_path']);
        if (!$ffmpegPath) {
            Debug::log('Processor: Invalid FFmpeg path', $settings['_bfp_ffmpeg_path']); // DEBUG-REMOVE
            return false;
        }

        Debug::log('Processor: FFmpeg path validated', $ffmpegPath); // DEBUG-REMOVE

        // Get duration
        $duration = $this->getFfmpegDuration($ffmpegPath, $inputPath);
        if (!$duration) {
            Debug::log('Processor: Could not get duration'); // DEBUG-REMOVE
            return false;
        }

        $targetDuration = apply_filters('bfp_ffmpeg_time', floor($duration * $filePercent / 100));
        
        Debug::log('Processor: durations', [
            'original' => $duration,
            'target' => $targetDuration,
            'percent' => $filePercent
        ]); // DEBUG-REMOVE
        
        // Build command
        $command = $this->buildFfmpegCommand($ffmpegPath, $inputPath, $outputPath, $targetDuration, $settings['_bfp_ffmpeg_watermark']);
        
        Debug::log('Processor: FFmpeg command', $command); // DEBUG-REMOVE
        
        // Execute
        @shell_exec($command);
        
        $success = file_exists($outputPath);
        Debug::log('Processor: FFmpeg execution result', $success); // DEBUG-REMOVE
        
        return $success;
    }
    
    /**
     * Process audio with PHP fallback
     * 
     * @param string $inputPath Input file path
     * @param string $outputPath Output file path
     * @param int $filePercent Percentage to keep
     * @return bool Success status
     */
    private function processWithPhp(string $inputPath, string $outputPath, int $filePercent): bool {
        Debug::log('Processor: PHP processing', ['input' => $inputPath, 'output' => $outputPath, 'percent' => $filePercent]); // DEBUG-REMOVE
        
        try {
            require_once dirname(dirname(dirname(__FILE__))) . '/vendor/php-mp3/class.mp3.php';
            $mp3 = new \BFPMP3();
            $mp3->cut_mp3($inputPath, $outputPath, 0, $filePercent/100, 'percent', false);
            unset($mp3);
            $success = file_exists($outputPath);
            Debug::log('Processor: PHP MP3 processing result', $success); // DEBUG-REMOVE
            return $success;
        } catch (\Exception | \Error $e) {
            Debug::log('Processor: PHP processing error', $e->getMessage()); // DEBUG-REMOVE
            error_log('BFP MP3 processing error: ' . $e->getMessage());
            // Final fallback - simple truncate
            $this->fileManager->truncateFile($inputPath, $filePercent);
            return false;
        }
    }
    
    /**
     * Prepare FFmpeg path
     * 
     * @param string $path FFmpeg path setting
     * @return string|false Prepared path or false
     */
    private function prepareFfmpegPath(string $path): string|false {
        if (empty($path)) {
            return false;
        }
        
        $path = rtrim($path, '/');
        if (is_dir($path)) {
            $path .= '/ffmpeg';
        }
        
        return file_exists($path) ? $path : false;
    }
    
    /**
     * Get duration from FFmpeg
     * 
     * @param string $ffmpegPath FFmpeg executable path
     * @param string $inputPath Input file path
     * @return int|false Duration in seconds or false
     */
    private function getFfmpegDuration(string $ffmpegPath, string $inputPath): int|false {
        $command = sprintf('"%s" -i %s 2>&1', $ffmpegPath, escapeshellarg($inputPath));
        $output = @shell_exec($command);
        
        if (!$output) {
            return false;
        }
        
        if (preg_match('/Duration: (\d{2}):(\d{2}):(\d{2})/', $output, $matches)) {
            return ($matches[1] * 3600) + ($matches[2] * 60) + $matches[3];
        }
        
        return false;
    }
    
    /**
     * Build FFmpeg command
     * 
     * @param string $ffmpegPath FFmpeg path
     * @param string $inputPath Input file
     * @param string $outputPath Output file
     * @param int $duration Target duration
     * @param string $watermark Watermark URL
     * @return string Command
     */
    private function buildFfmpegCommand(string $ffmpegPath, string $inputPath, string $outputPath, int $duration, string $watermark = ''): string {
        $command = sprintf(
            '"%s" -hide_banner -loglevel panic -vn -i %s',
            $ffmpegPath,
            escapeshellarg($inputPath)
        );
        
        // Add watermark if available
        if (!empty($watermark)) {
            $watermarkPath = $this->fileManager->isLocal($watermark);
            if ($watermarkPath) {
                $watermarkPath = str_replace(['\\', ':', '.'], ['/', '\:', '\.'], $watermarkPath);
                $fadeStart = max(0, $duration - 2);
                $command .= sprintf(
                    ' -filter_complex "amovie=%s:loop=0,volume=0.3[s];[0][s]amix=duration=first,afade=t=out:st=%d:d=2"',
                    escapeshellarg($watermarkPath),
                    $fadeStart
                );
            }
        }
        
        $command .= sprintf(' -map 0:a -t %d -y %s', $duration, escapeshellarg($outputPath));
        
        return $command;
    }
    
    /**
     * Swap processed file with original
     * 
     * @param array $fileInfo File information array
     * @return void
     */
    private function swapProcessedFile(array &$fileInfo): void {
        if (@unlink($fileInfo['filePath'])) {
            if (@rename($fileInfo['oFilePath'], $fileInfo['filePath'])) {
                return;
            }
        }
        
        // If swap failed, use processed file directly
        $fileInfo['filePath'] = $fileInfo['oFilePath'];
        $fileInfo['fileName'] = $fileInfo['oFileName'];
    }
    
    /**
     * Get audio duration from file
     * 
     * @param string $filepath File path
     * @return int Duration in seconds
     */
    public function getAudioDuration(string $filepath): int {
        // Try ffprobe first
        if ($duration = $this->getDurationViaFfprobe($filepath)) {
            return $duration;
        }
        
        // Fallback to getID3
        if (class_exists('getID3')) {
            $getID3 = new \getID3();
            $info = $getID3->analyze($filepath);
            if (!empty($info['playtime_seconds'])) {
                return (int) $info['playtime_seconds'];
            }
        }
        
        return 0;
    }
    
    /**
     * Get duration using ffprobe
     * 
     * @param string $filepath File path
     * @return int Duration or 0
     */
    private function getDurationViaFfprobe(string $filepath): int {
        $ffprobe = '/usr/bin/ffprobe'; // Adjust path as needed
        
        if (!file_exists($ffprobe)) {
            return 0;
        }
        
        $cmd = escapeshellcmd($ffprobe) . ' -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 ' . escapeshellarg($filepath);
        $duration = shell_exec($cmd);
        
        return $duration ? (int) round((float) $duration) : 0;
    }
}
