<?php

/**
 * Global Helper Functions
 */

if (!function_exists('formatDate')) {
    /**
     * Format date to a readable format
     * 
     * @param string|null $date
     * @param string $format
     * @return string
     */
    function formatDate($date, $format = 'M d, Y')
    {
        if (!$date) {
            return 'N/A';
        }
        return date($format, strtotime($date));
    }
}

if (!function_exists('getInitials')) {
    /**
     * Get initials from a name
     * 
     * @param string $name
     * @return string
     */
    function getInitials($name)
    {
        $words = explode(' ', $name);
        $initials = '';
        
        foreach ($words as $word) {
            $initials .= strtoupper(substr($word, 0, 1));
        }
        
        return substr($initials, 0, 2);
    }
}

if (!function_exists('getRoleBadgeColor')) {
    /**
     * Get badge color based on role
     * 
     * @param string $role
     * @return string
     */
    function getRoleBadgeColor($role)
    {
        $colors = [
            'admin' => 'danger',
            'manager' => 'warning',
            'staff' => 'info',
            'user' => 'secondary',
            'employee' => 'primary',
        ];
        
        return $colors[strtolower($role)] ?? 'secondary';
    }
}

if (!function_exists('getStatusBadgeColor')) {
    /**
     * Get badge color based on status
     * 
     * @param string $status
     * @return string
     */
    function getStatusBadgeColor($status)
    {
        $colors = [
            'active' => 'success',
            'inactive' => 'secondary',
            'pending' => 'warning',
            'approved' => 'success',
            'rejected' => 'danger',
            'completed' => 'primary',
        ];
        
        return $colors[strtolower($status)] ?? 'secondary';
    }
}

if (!function_exists('truncateText')) {
    /**
     * Truncate text to specified length
     * 
     * @param string $text
     * @param int $length
     * @param string $suffix
     * @return string
     */
    function truncateText($text, $length = 50, $suffix = '...')
    {
        if (strlen($text) <= $length) {
            return $text;
        }
        
        return substr($text, 0, $length) . $suffix;
    }
}

if (!function_exists('displayCampus')) {
    /**
     * Display campus name or default value
     * 
     * @param string|null $campus
     * @return string
     */
    function displayCampus($campus)
    {
        return $campus ?: 'N/A';
    }
}

if (!function_exists('displayDepartment')) {
    /**
     * Display department name or default value
     * 
     * @param object|null $department
     * @return string
     */
    function displayDepartment($department)
    {
        return $department ? $department->department_name : 'N/A';
    }
}

if (!function_exists('activeMenu')) {
    /**
     * Check if current route matches and return active class
     * 
     * @param string $route
     * @param string $class
     * @return string
     */
    function activeMenu($route, $class = 'active')
    {
        return request()->routeIs($route) ? $class : '';
    }
}

if (!function_exists('generateAvatarColor')) {
    /**
     * Generate a consistent color based on name
     * 
     * @param string $name
     * @return string
     */
    function generateAvatarColor($name)
    {
        $colors = [
            'primary', 'secondary', 'success', 'danger', 
            'warning', 'info', 'dark'
        ];
        
        $index = ord(strtolower($name[0])) % count($colors);
        return $colors[$index];
    }
}

if (!function_exists('getCampuses')) {
    /**
     * Get all campuses from Globalpreferrence
     * 
     * @return array
     */
    function getCampuses()
    {
        return \App\Helpers\Globalpreferrence::Campuses();
    }
}

if (!function_exists('getCampusNames')) {
    /**
     * Get only campus names for filters
     * Get only campus names for filters
     * 
     * @return array
     */
    function getCampusNames()
    {
        $campuses = \App\Helpers\Globalpreferrence::Campuses();
        return array_column($campuses, 'Campus');
    }
}

if (!function_exists('getDepartments')) {
    /**
     * Get all unique departments from database
     * 
     * @return array
     */
    function getDepartments()
    {
        $departments = \App\Models\Department::distinct()
            ->pluck('department_name')
            ->sort()
            ->values()
            ->toArray();
        
        return $departments;
    }
}

if (!function_exists('getCampusName')) {
    /**
     * Get campus name from Globalpreferrence by ID or code
     * 
     * @param string|int|null $campusValue
     * @return string
     */
    function getCampusName($campusValue)
    {
        if (!$campusValue) {
            return 'N/A';
        }

        $campuses = \App\Helpers\Globalpreferrence::Campuses();
        
        // Check if value is numeric (ID from database)
        if (is_numeric($campusValue)) {
            foreach ($campuses as $campus) {
                if ($campus['ID'] == $campusValue) {
                    return $campus['Campus'];
                }
            }
        }
        
        // Check if value is a key (code) like 'SG', 'MCC', etc.
        if (isset($campuses[$campusValue])) {
            return $campuses[$campusValue]['Campus'];
        }
        
        // Check if value is a campus name directly
        foreach ($campuses as $campus) {
            if ($campus['Campus'] === $campusValue) {
                return $campus['Campus'];
            }
        }
        
        // Return the value as-is if not found
        return $campusValue;
    }
}

if (!function_exists('getCampusColor')) {
    /**
     * Get campus color from Globalpreferrence by ID or code
     * 
     * @param string|int|null $campusValue
     * @return string
     */
    function getCampusColor($campusValue)
    {
        if (!$campusValue) {
            return 'secondary';
        }

        $campuses = \App\Helpers\Globalpreferrence::Campuses();
        
        // Check if value is numeric (ID from database)
        if (is_numeric($campusValue)) {
            foreach ($campuses as $campus) {
                if ($campus['ID'] == $campusValue) {
                    return $campus['Color'];
                }
            }
        }
        
        // Check if value is a key (code)
        if (isset($campuses[$campusValue])) {
            return $campuses[$campusValue]['Color'];
        }
        
        // Check if value is a campus name
        foreach ($campuses as $campus) {
            if ($campus['Campus'] === $campusValue) {
                return $campus['Color'];
            }
        }
        
        return 'secondary';
    }
}

if (!function_exists('encryptId')) {
    /**
     * Encrypt an ID for use in URLs
     * 
     * @param int|string $id
     * @return string
     */
    function encryptId($id)
    {
        try {
            return \Illuminate\Support\Facades\Crypt::encryptString((string)$id);
        } catch (\Exception $e) {
            return $id;
        }
    }
}

if (!function_exists('decryptId')) {
    /**
     * Decrypt an encrypted ID from URL
     * 
     * @param string $encryptedId
     * @return int|null
     */
    function decryptId($encryptedId)
    {
        try {
            $decrypted = \Illuminate\Support\Facades\Crypt::decryptString($encryptedId);
            return is_numeric($decrypted) ? (int)$decrypted : null;
        } catch (\Exception $e) {
            // If decryption fails, check if it's already a numeric ID (backward compatibility)
            return is_numeric($encryptedId) ? (int)$encryptedId : null;
        }
    }
}

if (!function_exists('encryptIds')) {
    /**
     * Encrypt multiple IDs
     * 
     * @param array $ids
     * @return array
     */
    function encryptIds(array $ids)
    {
        return array_map(function($id) {
            return encryptId($id);
        }, $ids);
    }
}

if (!function_exists('routeWithEncryptedId')) {
    /**
     * Generate a route with encrypted ID
     * 
     * @param string $routeName
     * @param int|string $id
     * @param array $parameters
     * @return string
     */
    function routeWithEncryptedId($routeName, $id, array $parameters = [])
    {
        $encryptedId = encryptId($id);
        return route($routeName, array_merge([$encryptedId], $parameters));
    }
}
