Demo File Creation

•  Demo File Path: Demos are created in /wp-content/uploads/bandfront-player-files/.
•  Demo Creation: If a demo file does not exist, it's created by copying a portion of the original file.
•  Functions:
◦  getDemoFile: Checks if the demo exists; if not, it creates one.
◦  createDemoFile: Creates the demo file by downloading or copying the source.
◦  truncateFile: Truncates the demo to a specified percentage.

Handling Demo Streaming

•  StreamController:
•  Demos Trigger: When demos are enabled, non-purchased users are served demo versions.
•  Stream Request Handling: 
◦  If demos are enabled and the user hasn't purchased the product, a demo file is served.
◦  The system checks if the file exists and streams it using the Audio component.
◦  For non-demos, it serves the full file if demos are disabled.

Key Takeaways

1. Directories:
•  Ensure /bandfront-player-files/ and its subdirectories like /demos/ exist.
2. Demo Mode:
•  If no demos exist, demo creation logic kicks in.
•  Demo files are fetched or generated when necessary.
3. Security and Access:
•  In demo mode, stricter checks (purchase verification) aren't enforced.