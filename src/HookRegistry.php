<?php 

namespace Riyad\Polypay;

use Riyad\Polypay\Contracts\HookRegistryContract;
use Riyad\Polypay\Constants\HookReturnMode;
use Riyad\Polypay\Exceptions\HookException;
use Riyad\Polypay\Exceptions\HookRegistrationException;
use Riyad\Polypay\Exceptions\HookValidationException;
use Riyad\Polypay\Exceptions\HookNotFoundException;

/**
 * Class HookRegistry
 *
 * Singleton registry for managing hooks (callables, class names, or instances)
 * with configurable priorities, contracts, and return modes.
 *
 * Implements the HookRegistryContract.
 */
final class HookRegistry implements HookRegistryContract
{
    /**
     * Singleton instance of HookRegistry.
     */
    private static ?self $instance = null;

    /**
     * Registered hooks storage.
     *
     * Structure:
     * ['hookName' => [['hook' => mixed, 'priority' => int, 'contracts' => array|null], ...], ...]
     *
     * @var array<string, array<int, array{hook:mixed,priority:int,contracts:?array}>>
     */
    private array $hooks = [];

    /**
     * Hook configuration storage.
     *
     * Structure:
     * ['hookName' => ['allowMultiple' => bool, 'defaultPriority' => int, 'returnMode' => string, 'strictContracts' => array|null]]
     *
     * @var array<string, array{allowMultiple:bool,defaultPriority:int,returnMode:string,strictContracts:?array}>
     */
    private array $hookConfig = [];

    /**
     * Private constructor to prevent direct instantiation.
     */
    private function __construct() {}

    /**
     * Initialize the singleton registry instance.
     *
     * @return self Initialized HookRegistry instance
     */
    public static function init(): self
    {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Get the existing singleton instance.
     *
     * @return self HookRegistry instance
     * @throws \RuntimeException if init() was not called first
     */
    public static function instance(): self
    {
        if (!self::$instance) {
            throw new \RuntimeException("HookRegistry not initialized. Call HookRegistry::init() first.");
        }
        return self::$instance;
    }

    /**
     * Configure a hook name with settings.
     *
     * @param string $hookName Name of the hook
     * @param bool $allowMultiple Whether multiple hooks under this name are allowed
     * @param int $defaultPriority Default priority for registered hooks
     * @param string $returnMode How to handle return values (HookReturnMode::IGNORE or HookReturnMode::SINGLE)
     * @param array|null $strictContracts List of interface names the hook must implement
     * @throws \InvalidArgumentException
     */
    public function configureHook(
        string $hookName,
        bool $allowMultiple = true,
        int $defaultPriority = 0,
        string $returnMode = HookReturnMode::IGNORE,
        ?array $strictContracts = null
    ): void {
        if ($defaultPriority < PHP_INT_MIN || $defaultPriority > PHP_INT_MAX) {
            throw new \InvalidArgumentException('defaultPriority must be an integer');
        }

        if (!in_array($returnMode, [HookReturnMode::IGNORE, HookReturnMode::SINGLE], true)) {
            throw new \InvalidArgumentException('Invalid returnMode');
        }

        $this->hookConfig[$hookName] = [
            'allowMultiple' => $allowMultiple,
            'defaultPriority' => $defaultPriority,
            'returnMode' => $returnMode,
            'strictContracts' => $strictContracts,
        ];
    }

    /**
     * Register a hook: callable, instance, or class name (string).
     *
     * @param string $hookName Name of the hook
     * @param callable|object|string $hook The hook to register
     * @param int|null $priority Optional priority for this hook
     * @param array|null $contracts Optional contracts the hook must implement
     * @throws HookRegistrationException
     */
    public function register(string $hookName, $hook, ?int $priority = null, ?array $contracts = null): void
    {
        if (!is_callable($hook) && !is_string($hook) && !is_object($hook)) {
            throw new HookRegistrationException('Hook must be a callable, class name string, or object instance.');
        }

        if ($contracts !== null) {
            foreach ($contracts as $contract) {
                if (!is_string($contract) || !interface_exists($contract)) {
                    throw new HookRegistrationException("Contract '{$contract}' must be an existing interface name.");
                }
            }
        }

        $cfg = $this->hookConfig[$hookName] ?? [
            'allowMultiple' => true,
            'defaultPriority' => 0,
            'returnMode' => HookReturnMode::IGNORE,
            'strictContracts' => null,
        ];

        $priority = $priority ?? $cfg['defaultPriority'];
        $allowMultiple = $cfg['allowMultiple'];
        $effectiveContracts = $contracts ?? $cfg['strictContracts'];

        if (is_callable($hook) && $effectiveContracts !== null) {
            throw new HookRegistrationException('When registering a callable you must not pass contracts.');
        }

        if (is_string($hook)) {
            if (!class_exists($hook)) {
                throw new HookRegistrationException("Hook class '{$hook}' does not exist.");
            }
            if ($effectiveContracts !== null) {
                $this->assertClassImplementsContracts($hook, $effectiveContracts);
            }
        }

        if (is_object($hook) && $effectiveContracts !== null) {
            $this->assertInstanceImplementsContracts($hook, $effectiveContracts);
        }

        if (!isset($this->hooks[$hookName])) {
            $this->hooks[$hookName] = [];
        }

        if (!$allowMultiple) {
            $this->hooks[$hookName] = [];
        }

        foreach ($this->hooks[$hookName] as $entry) {
            if ($this->areHooksIdentical($entry['hook'], $hook) && $entry['contracts'] === $effectiveContracts) {
                return;
            }
        }

        $this->hooks[$hookName][] = [
            'hook' => $hook,
            'priority' => $priority,
            'contracts' => $effectiveContracts,
        ];

        usort($this->hooks[$hookName], static fn($a, $b) => $b['priority'] <=> $a['priority']);
    }

    /**
     * Remove a registered hook (no exception if not found).
     *
     * @param string $hookName Name of the hook
     * @param callable|object|string $hook Hook to remove
     */
    public function remove(string $hookName, $hook): void
    {
        if (!isset($this->hooks[$hookName])) {
            return;
        }

        foreach ($this->hooks[$hookName] as $i => $entry) {
            if ($this->areHooksIdentical($entry['hook'], $hook)) {
                unset($this->hooks[$hookName][$i]);
            }
        }

        $this->hooks[$hookName] = array_values($this->hooks[$hookName]);
    }

    /**
     * Execute all hooks for a given hook name.
     *
     * @param string $hookName Name of the hook
     * @param mixed ...$args Arguments passed to each hook
     * @return mixed|null Result of the executed hook (if single), or null
     * @throws HookValidationException
     */
    public function execute(string $hookName, ...$args)
    {
        if (!isset($this->hooks[$hookName]) || empty($this->hooks[$hookName])) {
            return null;
        }

        $cfg = $this->hookConfig[$hookName] ?? [
            'allowMultiple' => true,
            'defaultPriority' => 0,
            'returnMode' => HookReturnMode::IGNORE,
            'strictContracts' => null,
        ];

        $allowMultiple = $cfg['allowMultiple'];
        $returnMode = $cfg['returnMode'] ?? HookReturnMode::IGNORE;
        $result = null;

        foreach ($this->hooks[$hookName] as $entry) {
            $hook = $entry['hook'];
            $callable = null;

            if (!empty($entry['contracts'])) {
                if (is_string($hook)) {
                    $instance = new $hook();
                    $this->assertInstanceImplementsContracts($instance, $entry['contracts']);
                    $callable = is_callable($instance) ? $instance : (method_exists($instance, 'handle') ? [$instance, 'handle'] : (method_exists($instance, 'execute') ? [$instance, 'execute'] : null));
                } else {
                    $instance = $hook;
                    $this->assertInstanceImplementsContracts($instance, $entry['contracts']);
                    $callable = is_callable($instance) ? $instance : (method_exists($instance, 'handle') ? [$instance, 'handle'] : (method_exists($instance, 'execute') ? [$instance, 'execute'] : null));
                }

                if ($callable === null) {
                    throw new HookValidationException('Registered hook instance/class implements the contract but is not callable.');
                }
            } else {
                if (is_string($hook)) {
                    $instance = new $hook();
                    $callable = is_callable($instance) ? $instance : (method_exists($instance, 'handle') ? [$instance, 'handle'] : (method_exists($instance, 'execute') ? [$instance, 'execute'] : null));
                    if ($callable === null) {
                        continue;
                    }
                } elseif (is_object($hook)) {
                    $callable = is_callable($hook) ? $hook : (method_exists($hook, 'handle') ? [$hook, 'handle'] : (method_exists($hook, 'execute') ? [$hook, 'execute'] : null));
                    if ($callable === null) {
                        continue;
                    }
                } else {
                    $callable = $hook;
                }
            }

            $value = $callable(...$args);

            if (!$allowMultiple && $returnMode === HookReturnMode::SINGLE) {
                return $value;
            }
        }

        return null;
    }

    /**
     * Check if any hooks are registered under a hook name.
     *
     * @param string $hookName Name of the hook
     * @return bool True if hooks exist, false otherwise
     */
    public function hasHooks(string $hookName): bool
    {
        return !empty($this->hooks[$hookName]);
    }

    /**
     * Get all registered hooks for a hook name.
     *
     * @param string $hookName Name of the hook
     * @return array List of registered hook descriptors
     * @throws HookNotFoundException If no hooks are registered under the given name
     */
    public function getHooks(string $hookName): array
    {
        if (empty($this->hooks[$hookName])) {
            throw new HookNotFoundException("No hooks registered for '{$hookName}'.");
        }

        return $this->hooks[$hookName];
    }

    /**
     * Validate that a class implements all required contracts.
     *
     * @param string $class Class name
     * @param array $contracts List of interface names
     * @throws HookValidationException
     */
    private function assertClassImplementsContracts(string $class, array $contracts): void
    {
        $implemented = class_implements($class) ?: [];
        foreach ($contracts as $contract) {
            if (!in_array($contract, $implemented, true)) {
                throw new HookValidationException("Hook class '{$class}' must implement interface '{$contract}'.");
            }
        }
    }

    /**
     * Validate that an object instance implements all required contracts.
     *
     * @param object $instance Object instance
     * @param array $contracts List of interface names
     * @throws HookValidationException
     */
    private function assertInstanceImplementsContracts(object $instance, array $contracts): void
    {
        $implemented = class_implements($instance) ?: [];
        foreach ($contracts as $contract) {
            if (!in_array($contract, $implemented, true)) {
                $cn = get_class($instance);
                throw new HookValidationException("Hook instance of '{$cn}' must implement interface '{$contract}'.");
            }
        }
    }

    /**
     * Determine whether two hooks are identical (used to prevent duplicates).
     *
     * @param callable|object|string $a
     * @param callable|object|string $b
     * @return bool True if identical, false otherwise
     */
    private function areHooksIdentical($a, $b): bool
    {
        if (is_string($a) && is_string($b)) {
            return $a === $b;
        }

        if ($a instanceof \Closure && $b instanceof \Closure) {
            return spl_object_hash($a) === spl_object_hash($b);
        }

        if (is_object($a) && is_object($b)) {
            return $a === $b;
        }

        return $a === $b;
    }
}

// final class HookRegistry implements HookRegistryContract
// {
//         private static ?self $instance = null;
        
//     /**
//      * ['hookName' => [['hook' => ..., 'priority' => int, 'contracts' => array|null], ...], ...]
//      * @var array<string, array<int, array{hook:mixed,priority:int,contracts:?array}>>
//      */
//     private array $hooks = [];

//     /**
//      * ['hookName' => ['allowMultiple' => bool, 'defaultPriority' => int, 'returnMode' => string, 'strictContracts' => array|null]]
//      * @var array<string, array{allowMultiple:bool,defaultPriority:int,returnMode:string,strictContracts:?array}>
//      */
//     private array $hookConfig = [];

//     /**
//      * Private constructor to prevent direct instantiation.
//      */
//     private function __construct() {}

//     /**
//      * Initialize registry once (optional metadata bootstrapping).
//      */
//     public static function init(): self
//     {
//         if (!self::$instance) {
//             self::$instance = new self();
//         }
//         return self::$instance;
//     }

//     /**
//      * Get singleton instance.
//      */
//     public static function instance(): self
//     {
//         if (!self::$instance) {
//             throw new \RuntimeException("GatewayRegistry not initialized. Call GatewayRegistry::init() first.");
//         }
//         return self::$instance;
//     }

//     /**
//      * Configure a hook name.
//      */
//     public function configureHook(
//         string $hookName,
//         bool $allowMultiple = true,
//         int $defaultPriority = 0,
//         string $returnMode = HookReturnMode::IGNORE,
//         ?array $strictContracts = null
//     ): void {
//         if ($defaultPriority < PHP_INT_MIN || $defaultPriority > PHP_INT_MAX) {
//             throw new \InvalidArgumentException('defaultPriority must be an integer');
//         }

//         if (!in_array($returnMode, [HookReturnMode::IGNORE, HookReturnMode::SINGLE], true)) {
//             throw new \InvalidArgumentException('Invalid returnMode');
//         }

//         $this->hookConfig[$hookName] = [
//             'allowMultiple' => $allowMultiple,
//             'defaultPriority' => $defaultPriority,
//             'returnMode' => $returnMode,
//             'strictContracts' => $strictContracts,
//         ];
//     }

//     /**
//      * Register a hook: callable, instance, or class name (string).
//      *
//      * If $contracts is provided, the registry will validate that the hook (class/instance)
//      * implements all of those contract interface names.
//      *
//      * Note: callables are accepted only when $contracts === null (because you cannot reliably
//      * validate a plain callable against an interface).
//      *
//      * @param callable|object|string $hook
//      * @param array|null $contracts
//      * @throws HookRegistrationException
//      */
//     public function register(string $hookName, $hook, ?int $priority = null, ?array $contracts = null): void
//     {
//         // Validate basic hook type
//         if (!is_callable($hook) && !is_string($hook) && !is_object($hook)) {
//             throw new HookRegistrationException('Hook must be a callable, class name string, or object instance.');
//         }

//         // If contracts were supplied, they must be valid interface FQCNs
//         if ($contracts !== null) {
//             foreach ($contracts as $contract) {
//                 if (!is_string($contract) || !interface_exists($contract)) {
//                     throw new HookRegistrationException("Contract '{$contract}' must be an existing interface name.");
//                 }
//             }
//         }

//         $cfg = $this->hookConfig[$hookName] ?? [
//             'allowMultiple' => true,
//             'defaultPriority' => 0,
//             'returnMode' => HookReturnMode::IGNORE,
//             'strictContracts' => null,
//         ];

//         $priority = $priority ?? $cfg['defaultPriority'];
//         $allowMultiple = $cfg['allowMultiple'];

//         // If user provided global strictContracts via configureHook but also provided contracts here,
//         // merge them (registration-level contracts take precedence/are required).
//         $effectiveContracts = $contracts ?? $cfg['strictContracts'];

//         // Callables cannot be validated against interfaces; require null contracts for callables
//         if (is_callable($hook) && $effectiveContracts !== null) {
//             throw new HookRegistrationException('When registering a callable you must not pass contracts because callables cannot be validated against interfaces. Register a class name or instance instead.');
//         }

//         // If hook is class-name string: ensure the class exists and implements required contracts
//         if (is_string($hook)) {
//             if (!class_exists($hook)) {
//                 throw new HookRegistrationException("Hook class '{$hook}' does not exist.");
//             }
//             if ($effectiveContracts !== null) {
//                 $this->assertClassImplementsContracts($hook, $effectiveContracts);
//             }
//         }

//         // If hook is instance: validate instance implements required contracts (if provided)
//         if (is_object($hook)) {
//             if ($effectiveContracts !== null) {
//                 $this->assertInstanceImplementsContracts($hook, $effectiveContracts);
//             }
//         }

//         // Initialize storage
//         if (!isset($this->hooks[$hookName])) {
//             $this->hooks[$hookName] = [];
//         }

//         // If multiple not allowed, override existing
//         if (!$allowMultiple) {
//             $this->hooks[$hookName] = [];
//         }

//         // Prevent exact duplicate registration (same hook identity + same contracts + same priority)
//         foreach ($this->hooks[$hookName] as $entry) {
//             if ($this->areHooksIdentical($entry['hook'], $hook) && $entry['contracts'] === $effectiveContracts) {
//                 // already registered -> do nothing
//                 return;
//             }
//         }

//         $this->hooks[$hookName][] = [
//             'hook' => $hook,
//             'priority' => $priority,
//             'contracts' => $effectiveContracts,
//         ];

//         // Sort once by priority descending
//         usort($this->hooks[$hookName], static fn($a, $b) => $b['priority'] <=> $a['priority']);
//     }

//     /**
//      * Remove a registered hook (no exception if not found).
//      *
//      * @param callable|object|string $hook
//      */
//     public function remove(string $hookName, $hook): void
//     {
//         if (!isset($this->hooks[$hookName])) {
//             return;
//         }

//         foreach ($this->hooks[$hookName] as $i => $entry) {
//             if ($this->areHooksIdentical($entry['hook'], $hook)) {
//                 unset($this->hooks[$hookName][$i]);
//             }
//         }

//         $this->hooks[$hookName] = array_values($this->hooks[$hookName]);
//     }

//     /**
//      * Execute hooks for a hook name.
//      *
//      * - If the hook is configured as multiple (allowMultiple = true), the result is always null.
//      * - If the hook is single (allowMultiple = false) and returnMode == SINGLE, returns the value from the single hook (first executed).
//      *
//      * @return mixed|null
//      * @throws HookValidationException
//      */
//     public function execute(string $hookName, ...$args)
//     {
//         if (!isset($this->hooks[$hookName]) || empty($this->hooks[$hookName])) {
//             return null;
//         }

//         $cfg = $this->hookConfig[$hookName] ?? [
//             'allowMultiple' => true,
//             'defaultPriority' => 0,
//             'returnMode' => HookReturnMode::IGNORE,
//             'strictContracts' => null,
//         ];

//         $allowMultiple = $cfg['allowMultiple'];
//         $returnMode = $cfg['returnMode'] ?? HookReturnMode::IGNORE;

//         $result = null;

//         foreach ($this->hooks[$hookName] as $entry) {
//             $hook = $entry['hook'];

//             // If registration provided contracts for this entry, validate instance/class now (defensive)
//             if (!empty($entry['contracts'])) {
//                 if (is_string($hook)) {
//                     // class name -> instantiate and validate instance at call time
//                     $instance = new $hook();
//                     $this->assertInstanceImplementsContracts($instance, $entry['contracts']);
//                     $callable = [$instance, 'handle'] ?? [$instance, 'execute'] ?? null;
//                     // prefer 'handle' or 'execute' methods; fallback to invoking as object if it is invokable
//                     if (is_callable($instance)) {
//                         $callable = $instance;
//                     }
//                 } else {
//                     // instance
//                     $instance = $hook;
//                     $this->assertInstanceImplementsContracts($instance, $entry['contracts']);
//                     $callable = is_callable($instance) ? $instance : (method_exists($instance, 'handle') ? [$instance, 'handle'] : (method_exists($instance, 'execute') ? [$instance, 'execute'] : null));
//                 }

//                 if ($callable === null) {
//                     throw new HookValidationException('Registered hook instance/class implements the contract but is not callable (no handle/execute method and not invokable).');
//                 }
//             } else {
//                 // no contracts specified -> hook must be callable
//                 if (is_string($hook)) {
//                     // class string without contracts: instantiate and attempt to call 'handle' or 'execute' or __invoke
//                     if (!class_exists($hook)) {
//                         continue; // defensive
//                     }
//                     $instance = new $hook();
//                     $callable = is_callable($instance) ? $instance : (method_exists($instance, 'handle') ? [$instance, 'handle'] : (method_exists($instance, 'execute') ? [$instance, 'execute'] : null));
//                     if ($callable === null) {
//                         // class with no callable entry -> skip safely
//                         continue;
//                     }
//                 } elseif (is_object($hook)) {
//                     $callable = is_callable($hook) ? $hook : (method_exists($hook, 'handle') ? [$hook, 'handle'] : (method_exists($hook, 'execute') ? [$hook, 'execute'] : null));
//                     if ($callable === null) {
//                         continue;
//                     }
//                 } else {
//                     // callable (function/closure/array)
//                     $callable = $hook;
//                 }
//             }

//             // Execute and capture return
//             $value = $callable(...$args);

//             // Only return value for single hooks (explicit rule)
//             if (!$allowMultiple && $returnMode === HookReturnMode::SINGLE) {
//                 return $value;
//             }

//             // else continue (multiple hooks ignore returns)
//         }

//         return null;
//     }

//     public function hasHooks(string $hookName): bool
//     {
//         return !empty($this->hooks[$hookName]);
//     }

//     public function getHooks(string $hookName): array
//     {
//         return $this->hooks[$hookName] ?? [];
//     }

//     /**
//      * Helpers
//      */

//     /**
//      * Verify a class implements all contracts (interface names).
//      *
//      * @param string $class
//      * @param array $contracts
//      * @throws HookValidationException
//      */
//     private function assertClassImplementsContracts(string $class, array $contracts): void
//     {
//         $implemented = class_implements($class) ?: [];
//         foreach ($contracts as $contract) {
//             if (!in_array($contract, $implemented, true)) {
//                 throw new HookValidationException("Hook class '{$class}' must implement interface '{$contract}'.");
//             }
//         }
//     }

//     /**
//      * Verify an instance implements all contracts (interface names).
//      *
//      * @param object $instance
//      * @param array $contracts
//      * @throws HookValidationException
//      */
//     private function assertInstanceImplementsContracts(object $instance, array $contracts): void
//     {
//         $implemented = class_implements($instance) ?: [];
//         foreach ($contracts as $contract) {
//             if (!in_array($contract, $implemented, true)) {
//                 $cn = get_class($instance);
//                 throw new HookValidationException("Hook instance of '{$cn}' must implement interface '{$contract}'.");
//             }
//         }
//     }

//     /**
//      * Determine whether two hook descriptors are identical (for dedup)
//      *
//      * @param callable|object|string $a
//      * @param callable|object|string $b
//      */
//     private function areHooksIdentical($a, $b): bool
//     {
//         // Same string name
//         if (is_string($a) && is_string($b)) {
//             return $a === $b;
//         }

//         // Both closures or callables: compare by spl_object_hash for closures or serialized representation for arrays
//         if ($a instanceof \Closure && $b instanceof \Closure) {
//             return spl_object_hash($a) === spl_object_hash($b);
//         }

//         // Object identity
//         if (is_object($a) && is_object($b)) {
//             return $a === $b;
//         }

//         // Mixed types: for array callables/other forms - try strict comparison
//         return $a === $b;
//     }
// }