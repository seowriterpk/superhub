
<?php
namespace VidSocial\Controllers;

/**
 * Age Verification Controller
 * Handles age verification with server-side validation
 */
class AgeController extends BaseController
{
    public function verify(): void
    {
        // Validate CSRF token
        if (!$this->validateCsrfToken()) {
            http_response_code(403);
            echo json_encode(['error' => 'Invalid request']);
            return;
        }
        
        $verified = $_POST['verified'] ?? false;
        
        if ($verified === 'yes') {
            // Set server-side session
            $_SESSION['age_verified'] = true;
            $_SESSION['age_verified_time'] = time();
            
            // Set cookie as backup (but rely on session for security)
            setcookie('age_verified', '1', time() + (365 * 24 * 60 * 60), '/', '', true, true);
            
            echo json_encode(['success' => true, 'redirect' => '/']);
        } else {
            echo json_encode(['success' => false, 'redirect' => 'https://www.google.com']);
        }
    }
    
    public function check(): bool
    {
        // Check session first (more secure)
        if (isset($_SESSION['age_verified']) && $_SESSION['age_verified'] === true) {
            return true;
        }
        
        // Fallback to cookie (less secure but maintains UX)
        return isset($_COOKIE['age_verified']) && $_COOKIE['age_verified'] === '1';
    }
    
    private function validateCsrfToken(): bool
    {
        $token = $_POST['csrf_token'] ?? '';
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
}
