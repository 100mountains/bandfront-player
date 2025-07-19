<?php
declare(strict_types=1);

namespace Bandfront\UI;

use Bandfront\Core\Config;
use Bandfront\Storage\FileManager;
use Bandfront\Utils\Debug;
use Bandfront\UI\DbRenderer;
use Bandfront\Db\Monitor;

// Set domain for UI
Debug::domain('ui');

/**
 * Admin rendering functionality
 * 
 * @package Bandfront\UI
 * @since 2.0.0
 */
class AdminRenderer {
    
    /**
     * Render settings page
     */
    public function renderSettingsPage(Config $config, FileManager $fileManager): void {
        echo '<div class="wrap">';
        Debug::log('AdminRenderer.php: Including global-admin-options.php', []); // DEBUG-REMOVE
        
        // Make dependencies available to the template
        // Note: The template uses these variables directly
        $renderer = $this; // Make renderer available for any helper methods
        
        // Create DbRenderer and Monitor instances for dev-tools template
        // This is the modern way: AdminRenderer manages dependencies and injects them into templates
        $monitor = new Monitor();
        $dbRenderer = new DbRenderer($config, $monitor, $fileManager);
        
        include_once plugin_dir_path(dirname(dirname(__FILE__))) . 'templates/global-admin-options.php';
        echo '</div>';
        
        Debug::log('AdminRenderer.php: Finished rendering settings page', []); // DEBUG-REMOVE
    }
    
    /**
     * Render admin notice
     */
    public function renderAdminNotice(array $notice): void {
        $class = 'notice notice-' . esc_attr($notice['type']) . ' is-dismissible';
        printf(
            '<div class="%1$s"><p>%2$s</p></div>', 
            esc_attr($class), 
            esc_html($notice['message'])
        );
    }
    
    /**
     * Render settings section
     */
    public function renderSettingsSection(string $title, string $icon, bool $expanded = false): string {
        ob_start();
        ?>
        <table class="widefat bfp-table-noborder">
            <tr>
                <table class="widefat bfp-settings-table">
                    <tr>
                        <td class="bfp-section-header">
                            <h2 onclick="jQuery(this).closest('table').find('.bfp-section-content').toggle(); jQuery(this).closest('.bfp-section-header').find('.bfp-section-arrow').toggleClass('bfp-section-arrow-open');" style="cursor: pointer;">
                                <?php echo esc_html($icon . ' ' . $title); ?>
                            </h2>
                            <span class="bfp-section-arrow<?php echo $expanded ? ' bfp-section-arrow-open' : ''; ?>">▶</span>
                        </td>
                    </tr>
                    <tbody class="bfp-section-content" style="display: <?php echo $expanded ? 'table-row-group' : 'none'; ?>;">
        <?php
        return ob_get_clean();
    }
    
    /**
     * Close settings section
     */
    public function closeSettingsSection(): string {
        return '</tbody></table></tr></table>';
    }
    
    /**
     * Render form field
     */
    public function renderField(string $type, string $name, $value, array $options = []): void {
        switch ($type) {
            case 'checkbox':
                $this->renderCheckbox($name, $value, $options);
                break;
            case 'text':
                $this->renderText($name, $value, $options);
                break;
            case 'select':
                $this->renderSelect($name, $value, $options);
                break;
            case 'textarea':
                $this->renderTextarea($name, $value, $options);
                break;
            case 'radio':
                $this->renderRadio($name, $value, $options);
                break;
            case 'number':
                $this->renderNumber($name, $value, $options);
                break;
        }
    }
    
    /**
     * Render checkbox field
     */
    private function renderCheckbox(string $name, $value, array $options): void {
        $id = $options['id'] ?? $name;
        $label = $options['label'] ?? '';
        $description = $options['description'] ?? '';
        
        ?>
        <input 
            type="checkbox" 
            id="<?php echo esc_attr($id); ?>" 
            name="<?php echo esc_attr($name); ?>" 
            value="1"
            <?php checked($value); ?>
            <?php echo isset($options['aria-label']) ? 'aria-label="' . esc_attr($options['aria-label']) . '"' : ''; ?>
        />
        <?php if ($label): ?>
            <label for="<?php echo esc_attr($id); ?>"><?php echo esc_html($label); ?></label>
        <?php endif; ?>
        <?php if ($description): ?>
            <br><em class="bfp-em-text"><?php echo esc_html($description); ?></em>
        <?php endif;
    }
    
    /**
     * Render text field
     */
    private function renderText(string $name, $value, array $options): void {
        $id = $options['id'] ?? $name;
        $class = $options['class'] ?? '';
        $placeholder = $options['placeholder'] ?? '';
        
        ?>
        <input 
            type="text" 
            id="<?php echo esc_attr($id); ?>" 
            name="<?php echo esc_attr($name); ?>" 
            value="<?php echo esc_attr($value); ?>"
            class="<?php echo esc_attr($class); ?>"
            placeholder="<?php echo esc_attr($placeholder); ?>"
            <?php echo isset($options['aria-label']) ? 'aria-label="' . esc_attr($options['aria-label']) . '"' : ''; ?>
        />
        <?php
    }
    
    /**
     * Render select field
     */
    private function renderSelect(string $name, $value, array $options): void {
        $id = $options['id'] ?? $name;
        $choices = $options['choices'] ?? [];
        
        ?>
        <select 
            id="<?php echo esc_attr($id); ?>" 
            name="<?php echo esc_attr($name); ?>"
            <?php echo isset($options['aria-label']) ? 'aria-label="' . esc_attr($options['aria-label']) . '"' : ''; ?>
        >
            <?php foreach ($choices as $choiceValue => $choiceLabel): ?>
                <option value="<?php echo esc_attr($choiceValue); ?>" <?php selected($value, $choiceValue); ?>>
                    <?php echo esc_html($choiceLabel); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php
    }
    
    /**
     * Render textarea field
     */
    private function renderTextarea(string $name, $value, array $options): void {
        $id = $options['id'] ?? $name;
        $class = $options['class'] ?? '';
        $rows = $options['rows'] ?? 4;
        
        ?>
        <textarea 
            id="<?php echo esc_attr($id); ?>" 
            name="<?php echo esc_attr($name); ?>" 
            class="<?php echo esc_attr($class); ?>"
            rows="<?php echo esc_attr($rows); ?>"
            <?php echo isset($options['aria-label']) ? 'aria-label="' . esc_attr($options['aria-label']) . '"' : ''; ?>
        ><?php echo esc_textarea($value); ?></textarea>
        <?php
    }
    
    /**
     * Render radio field group
     */
    private function renderRadio(string $name, $value, array $options): void {
        $choices = $options['choices'] ?? [];
        
        foreach ($choices as $choiceValue => $choiceLabel) {
            ?>
            <label>
                <input 
                    type="radio" 
                    name="<?php echo esc_attr($name); ?>" 
                    value="<?php echo esc_attr($choiceValue); ?>"
                    <?php checked($value, $choiceValue); ?>
                />
                <?php echo esc_html($choiceLabel); ?>
            </label><br>
            <?php
        }
    }
    
    /**
     * Render number field
     */
    private function renderNumber(string $name, $value, array $options): void {
        $id = $options['id'] ?? $name;
        $min = $options['min'] ?? '';
        $max = $options['max'] ?? '';
        $step = $options['step'] ?? '';
        
        ?>
        <input 
            type="number" 
            id="<?php echo esc_attr($id); ?>" 
            name="<?php echo esc_attr($name); ?>" 
            value="<?php echo esc_attr($value); ?>"
            <?php echo $min !== '' ? 'min="' . esc_attr($min) . '"' : ''; ?>
            <?php echo $max !== '' ? 'max="' . esc_attr($max) . '"' : ''; ?>
            <?php echo $step !== '' ? 'step="' . esc_attr($step) . '"' : ''; ?>
            <?php echo isset($options['aria-label']) ? 'aria-label="' . esc_attr($options['aria-label']) . '"' : ''; ?>
        />
        <?php
    }
}
