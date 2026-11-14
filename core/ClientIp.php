<?php

final class ClientIp {
    public static function get(): string {
        $remoteIp = $_SERVER['REMOTE_ADDR'] ?? '';
        if (!filter_var($remoteIp, FILTER_VALIDATE_IP)) {
            return '127.0.0.1';
        }

        $trustedProxies = array_filter(array_map(
            'trim',
            explode(',', getenv('TRUSTED_PROXY_IPS') ?: '')
        ));
        if (!in_array($remoteIp, $trustedProxies, true)) {
            return $remoteIp;
        }

        $cloudflareIp = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? '';
        if (filter_var($cloudflareIp, FILTER_VALIDATE_IP)) {
            return $cloudflareIp;
        }

        $forwardedChain = array_map(
            'trim',
            explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '')
        );
        $forwardedChain[] = $remoteIp;
        for ($i = count($forwardedChain) - 1; $i >= 0; $i--) {
            $candidate = $forwardedChain[$i];
            if (filter_var($candidate, FILTER_VALIDATE_IP)
                && !in_array($candidate, $trustedProxies, true)) {
                return $candidate;
            }
        }

        return $remoteIp;
    }
}
