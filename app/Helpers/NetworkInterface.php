<?php

namespace App\Helpers;

class NetworkInterface
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }
    
    public static function getIpAddressForInterface(string $interfaceName, int $ipFamily = \AF_INET): ?string
    {
        // Get all network interfaces
        // 1. Ambil semua data network interface
        $interfaces = net_get_interfaces();

        // 2. Cek apakah interface yang diminta ada dan memiliki alamat unicast
        if (!isset($interfaces[$interfaceName]) || !isset($interfaces[$interfaceName]['unicast'])) {
            return null; // Interface tidak ditemukan atau tidak punya alamat
        }

        // 3. Iterasi melalui setiap alamat di dalam 'unicast'
        foreach ($interfaces[$interfaceName]['unicast'] as $addressInfo) {
            // 4. Cek apakah alamat ini memiliki 'address' dan 'family' yang sesuai
            if (isset($addressInfo['address'], $addressInfo['family']) && $addressInfo['family'] === $ipFamily) {
                // Jika cocok, kembalikan alamat IP tersebut
                return $addressInfo['address'];
            }
        }
        // 5. Jika loop selesai dan tidak ada IP yang cocok ditemukan
        return null;
    }
}
