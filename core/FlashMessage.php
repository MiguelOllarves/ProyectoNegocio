<?php

/**
 * FlashMessage
 * Utility to manage flash messages using sessions.
 */
class FlashMessage {

    /**
     * Sets a flash message.
     * 
     * @param string $type The type of the message (success, error, warning, info)
     * @param string $message The message content
     */
    public static function set($type, $message) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['flash_messages'])) {
            $_SESSION['flash_messages'] = [];
        }
        
        $_SESSION['flash_messages'][] = [
            'type' => $type,
            'message' => $message
        ];
    }

    /**
     * Retrieves and clears all flash messages.
     * 
     * @return array Array of flash messages
     */
    public static function get() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $messages = $_SESSION['flash_messages'] ?? [];
        unset($_SESSION['flash_messages']);
        
        return $messages;
    }

    /**
     * Checks if there are any pending flash messages.
     * 
     * @return bool
     */
    public static function has() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        return !empty($_SESSION['flash_messages']);
    }
}
