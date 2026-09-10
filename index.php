<?php
/**
 * MOTOR UNIVERSAL DE CLOAKING SERVER-SIDE (PRO ENGINE MAX)
 * Detecção por Inteligência de IP, Reverse DNS, Headers de Navegador Real & User-Agent
 * 
 * ESTRUTURA UNIVERSAL:
 *  - safe.html  -> Sua Página Limpa (Safe Page)
 *  - black.html -> Sua VSL / Oferta Agressiva (Money Page)
 *  - index.php  -> Este Motor Universal (Plug & Play)
 */

// -------------------------------------------------------------
// 1. CONFIGURAÇÃO DE CAMINHOS DOS ARQUIVOS (WHITE / BLACK)
// -------------------------------------------------------------
$SAFE_PAGE  = __DIR__ . '/frequencia33.html'; // White Page
$BLACK_PAGE = __DIR__ . '/frequencia.html';   // Black Page (Money Page)

// Fallbacks de compatibilidade
if (!file_exists($SAFE_PAGE) && file_exists(__DIR__ . '/safe.html')) {
    $SAFE_PAGE = __DIR__ . '/safe.html';
}
if (!file_exists($BLACK_PAGE) && file_exists(__DIR__ . '/black.html')) {
    $BLACK_PAGE = __DIR__ . '/black.html';
}
if (!file_exists($SAFE_PAGE) && file_exists(__DIR__ . '/aoracaosecreta/index.html')) {
    $SAFE_PAGE = __DIR__ . '/aoracaosecreta/index.html';
}

// -------------------------------------------------------------
// 2. MODO BYPASS / PREVIEW DE ADMIN (Acesse: ?preview=vsl)
// -------------------------------------------------------------
if (isset($_GET['preview']) && $_GET['preview'] === 'vsl') {
    if (file_exists($BLACK_PAGE)) {
        include $BLACK_PAGE;
        exit();
    }
}

// -------------------------------------------------------------
// 3. CAPTURA DO IP REAL E CABEÇALHOS DO VISITANTE
// -------------------------------------------------------------
function getVisitorIP() {
    if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
        return $_SERVER['HTTP_CF_CONNECTING_IP'];
    }
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        return trim($ips[0]);
    }
    return $_SERVER['REMOTE_ADDR'] ?? '';
}

$visitorIP      = getVisitorIP();
$userAgent      = $_SERVER['HTTP_USER_AGENT'] ?? '';
$acceptLanguage = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '';

$isBot = false;
$blockReason = '';

// -------------------------------------------------------------
// 4. DETECÇÃO 1: FALTANDO ACCEPT-LANGUAGE (NAVEGADOR REAL SEMPRE ENVIA)
// -------------------------------------------------------------
// Navegadores de celular/desktop reais sempre enviam o idioma.
// Bots e ferramentas de moderação automatizadas em servidores raramente enviam.
if (empty($acceptLanguage)) {
    $isBot = true;
    $blockReason = "Faltando cabeçalho HTTP Accept-Language";
}

// -------------------------------------------------------------
// 5. DETECÇÃO 2: USER-AGENTS DE BOTS, CRAWLERS E SCRAPERS
// -------------------------------------------------------------
if (!$isBot) {
    $botUserAgents = [
        'facebookexternalhit', 'facebot', 'metainspector', 'googlebot', 
        'bingbot', 'yandexbot', 'baiduspider', 'python', 'curl', 'wget',
        'headlesschrome', 'bytespider', 'telegrambot', 'twitterbot', 
        'linkedinbot', 'whatsapp', 'phantomjs', 'selenium', 'puppeteer',
        'semrushbot', 'ahrefsbot', 'mj12bot', 'screaming frog'
    ];

    $lowerUA = strtolower($userAgent);
    foreach ($botUserAgents as $botUA) {
        if (strpos($lowerUA, $botUA) !== false) {
            $isBot = true;
            $blockReason = "User-Agent Detectado: " . $botUA;
            break;
        }
    }
}

// -------------------------------------------------------------
// 6. DETECÇÃO 3: REVERSE DNS (IDENTIFICA DATACENTERS EM TEMPO REAL)
// -------------------------------------------------------------
if (!$isBot && filter_var($visitorIP, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
    $hostName = @gethostbyaddr($visitorIP);
    if ($hostName && $hostName !== $visitorIP) {
        $lowerHost = strtolower($hostName);
        $datacenterKeywords = [
            'facebook', 'meta', 'google', 'amazonaws', 'cloudfront', 
            'digitalocean', 'hetzner', 'linode', 'ovh', 'datacenter', 
            'cloud', 'proxy', 'tor-exit', 'vultr', 'azure', 'leaseweb'
        ];

        foreach ($datacenterKeywords as $kw) {
            if (strpos($lowerHost, $kw) !== false) {
                $isBot = true;
                $blockReason = "Reverse DNS Datacenter: " . $hostName;
                break;
            }
        }
    }
}

// -------------------------------------------------------------
// 7. DETECÇÃO 4: FAIXAS DE IP CIDR CONHECIDAS DA META / FACEBOOK
// -------------------------------------------------------------
if (!$isBot && filter_var($visitorIP, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
    $metaIpRanges = [
        '31.13.24.0/21', '31.13.64.0/18', '45.64.40.0/22', '66.220.144.0/20',
        '69.63.176.0/20', '69.171.224.0/19', '74.119.76.0/22', '103.4.96.0/22',
        '129.134.0.0/16', '157.240.0.0/16', '173.252.64.0/18', '179.60.192.0/22',
        '185.60.216.0/22', '204.15.20.0/22'
    ];

    function checkCIDR($ip, $cidr) {
        if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) return false;
        list($subnet, $mask) = explode('/', $cidr);
        return (ip2long($ip) & ~((1 << (32 - (int)$mask)) - 1)) == ip2long($subnet);
    }

    foreach ($metaIpRanges as $range) {
        if (checkCIDR($visitorIP, $range)) {
            $isBot = true;
            $blockReason = "IP em Faixa Conhecida da Meta CIDR: " . $range;
            break;
        }
    }
}

// -------------------------------------------------------------
// 8. DECISÃO FINAL DE ENTREGA DA PÁGINA (RESPOSTA 200 OK)
// -------------------------------------------------------------
$hasParam = !empty($_GET);

if ($isBot || !$hasParam) {
    // ENTREGA A SAFE PAGE (Página Limpa para o Robô/Moderador)
    if (file_exists($SAFE_PAGE)) {
        include $SAFE_PAGE;
    } else {
        header("HTTP/1.1 404 Not Found");
        echo "Página Safe não encontrada.";
    }
} else {
    // ENTREGA A BLACK PAGE (VSL do Epstein para o Comprador Real)
    if (file_exists($BLACK_PAGE)) {
        include $BLACK_PAGE;
    } else {
        header("HTTP/1.1 404 Not Found");
        echo "Página de Oferta não encontrada.";
    }
}
exit();
