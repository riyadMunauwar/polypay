<?php

declare(strict_types=1);

namespace Riyad\PolyPay;

use Riyad\Hooks\Hook;

/**
 * Class PayHook
 *
 * A package-specific singleton wrapper around Hook.
 * Prevents cross-package singleton conflicts by
 * maintaining its own isolated Hook instance.
 *
 * Example:
 *  $PayHook = PayHook::instance();
 *  $PayHook->addAction('init', fn() => echo "App init");
 *  $PayHook->doAction('init');
 */
class PayHook
{
    /**
     * Singleton instance of PayHook.
     */
    private static ?PayHook $instance = null;

    /**
     * Internal Hook instance (isolated from global Hook::instance()).
     */
    private Hook $hook;

    /**
     * Private constructor to enforce singleton.
     */
    private function __construct()
    {
        // Create a dedicated Hook object (not using Hook::instance)
        $this->hook = Hook::make();
    }

    /**
     * Get singleton instance of PayHook.
     */
    public static function instance(): PayHook
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Proxy calls to the internal Hook instance.
     * This lets PayHook access all Hook methods transparently.
     */
    public function __call(string $name, array $arguments)
    {
        if (method_exists($this->hook, $name)) {
            return $this->hook->{$name}(...$arguments);
        }

        throw new \BadMethodCallException("Method {$name} does not exist on Hook.");
    }

    /**
     * Optionally expose the internal Hook instance if needed.
     */
    public function getHook(): Hook
    {
        return $this->hook;
    }
}