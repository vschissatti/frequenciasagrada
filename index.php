<?php
/**
 * MOTOR UNIVERSAL DE CLOAKING SERVER-SIDE (PRO ENGINE)
 * Detecção por Inteligência de IP, Reverse DNS, ASN de Datacenters & User-Agent
 * 
 * ESTRUTURA UNIVERSAL:
 *  - safe.html  -> Sua Página Limpa (Safe Page)
 *  - black.html -> Sua VSL / Oferta Agressiva (Money Page)
 *  - index.php  -> Este Motor Universal (Plug & Play)
 */

// -------------------------------------------------------------
// 1. CONFIGURAÇÃO DE CAMINHOS DOS ARQUIVOS
// -------------------------------------------------------------
$SAFE_PAGE  = __DIR__ . '/safe.html';
$BLACK_PAGE = __DIR__ . '/black.html';

// Fallback para safe page no subdiretório se não existir safe.html na raiz
if (!file_exists($SAFE_PAGE) && file_exists(__DIR__ . '/aoracaosecreta/index.html')) {
    $SAFE_PAGE = __DIR__ . '/aoracaosecreta/index.html';
}

// -------------------------------------------------------------
// 2. MODO BYPASS / PREVIEW DE ADMIN (Senha para você testar a VSL)
// -------------------------------------------------------------
// Acesse: seusite.com/?preview=vsl para ver a VSL direto sem filtros
if (isset($_GET['preview']) && $_GET['preview'] === 'vsl') {
    if (file_exists($BLACK_PAGE)) {
        include $BLACK_PAGE;
        exit();
    }
}

// -------------------------------------------------------------
// 3. CAPTURA DO IP REAL DO VISITANTE (SUPORTA CLOUDFLARE E PROXIES)
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

$visitorIP = getVisitorIP();
$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';

$isBot = false;
$blockReason = '';

// -------------------------------------------------------------
// 4. DETECÇÃO 1: USER-AGENTS DE BOTS, CRAWLERS E CRAWLERS HEADLESS
// -------------------------------------------------------------
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

// -------------------------------------------------------------
// 5. DETECÇÃO 2: REVERSE DNS (DESCOBRE SE O IP VEM DE DATACENTER/NUVEM)
// -------------------------------------------------------------
// O Reverse DNS consulta a quem pertence o IP diretamente na rede.
// Robôs do Meta, AWS, Google Cloud, DigitalOcean possuem nomes de servidor claros.
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
// 6. DETECÇÃO 3: FAIXAS DE IP CIDR CONHECIDAS DA META / FACEBOOK
// -------------------------------------------------------------
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

if (!$isBot && filter_var($visitorIP, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
    foreach ($metaIpRanges as $range) {
        if (checkCIDR($visitorIP, $range)) {
            $isBot = true;
            $blockReason = "IP em Faixa Conhecida da Meta CIDR: " . $range;
            break;
        }
    }
}

// -------------------------------------------------------------
// 7. DECISÃO FINAL DE ENTREGA DA PÁGINA (Sempre resposta 200 OK)
// -------------------------------------------------------------

// Regra de Ouro: SE FOR BOT OU DATACENTER, CAI NA SAFE PAGE INDEPENDENTE DE QUALQUER COISA.
// Se não for bot, mas não tiver parâmetros na URL, também cai na safe page (para evitar acessos diretos).
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
