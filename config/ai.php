<?php
/*
|--------------------------------------------------------------------------
| Gemini AI Tutor Configuration
|--------------------------------------------------------------------------
| Local Demo Mode is used when GEMINI_API_KEY is blank.
|
| Create an API key in Google AI Studio:
| https://aistudio.google.com/app/apikey
|
| IMPORTANT:
| - Keep the key in PHP only.
| - Never expose it in HTML or JavaScript.
| - Never commit a real key to GitHub.
*/

define('GEMINI_API_KEY', '');
define('GEMINI_MODEL', 'gemini-3.6-flash');
define('GEMINI_MAX_OUTPUT_TOKENS', 500);
?>
