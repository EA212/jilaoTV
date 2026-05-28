<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$DATA_FILE = __DIR__ . '/db.json';

$envFile = __DIR__ . '/.env.php';
$env = file_exists($envFile) ? include($envFile) : [];

$ADMIN_PASSWORD = $env['ADMIN_PASSWORD'] ?? 'admin';
$FORCE_UPDATE = ($env['FORCE_UPDATE'] ?? 'false') === 'true';
$TMDB_API_KEY = $env['TMDB_API_KEY'] ?? '';
$TMDB_PROXY_URL = $env['TMDB_PROXY_URL'] ?? '';

$DEFAULT_SITES = [
    ['key' => 'ffzy', 'name' => '非凡影视', 'api' => 'https://api.ffzyapi.com/api.php/provide/vod', 'active' => true],
    ['key' => 'bfzy', 'name' => '暴风资源', 'api' => 'https://bfzyapi.com/api.php/provide/vod', 'active' => true],
    ['key' => 'dyttzy', 'name' => '电影天堂', 'api' => 'http://caiji.dyttzyapi.com/api.php/provide/vod', 'active' => true],
    ['key' => 'tyyszy', 'name' => '天涯资源', 'api' => 'https://tyyszy.com/api.php/provide/vod', 'active' => true],
    ['key' => 'zy360', 'name' => '360资源', 'api' => 'https://360zy.com/api.php/provide/vod', 'active' => true],
    ['key' => 'maotaizy', 'name' => '茅台资源', 'api' => 'https://caiji.maotaizy.cc/api.php/provide/vod', 'active' => true],
    ['key' => 'wolong', 'name' => '卧龙资源', 'api' => 'https://wolongzyw.com/api.php/provide/vod', 'active' => true],
    ['key' => 'jisu', 'name' => '极速资源', 'api' => 'https://jszyapi.com/api.php/provide/vod', 'active' => true],
    ['key' => 'dbzy', 'name' => '豆瓣资源', 'api' => 'https://dbzy.tv/api.php/provide/vod', 'active' => true],
    ['key' => 'mozhua', 'name' => '魔爪资源', 'api' => 'https://mozhuazy.com/api.php/provide/vod', 'active' => true],
    ['key' => 'mdzy', 'name' => '魔都资源', 'api' => 'https://www.mdzyapi.com/api.php/provide/vod', 'active' => true],
    ['key' => 'zuid', 'name' => '最大资源', 'api' => 'https://api.zuidapi.com/api.php/provide/vod', 'active' => true],
    ['key' => 'yinghua', 'name' => '樱花资源', 'api' => 'https://m3u8.apiyhzy.com/api.php/provide/vod', 'active' => true],
    ['key' => 'wujin', 'name' => '无尽资源', 'api' => 'https://api.wujinapi.me/api.php/provide/vod', 'active' => true],
    ['key' => 'wwzy', 'name' => '旺旺短剧', 'api' => 'https://wwzy.tv/api.php/provide/vod', 'active' => true],
    ['key' => 'ikun', 'name' => 'iKun资源', 'api' => 'https://ikunzyapi.com/api.php/provide/vod', 'active' => true],
    ['key' => 'lzi', 'name' => '量子资源', 'api' => 'https://cj.lziapi.com/api.php/provide/vod', 'active' => true],
    ['key' => 'bdzy', 'name' => '百度资源', 'api' => 'https://api.apibdzy.com/api.php/provide/vod', 'active' => true],
    ['key' => 'hongniuzy', 'name' => '红牛资源', 'api' => 'https://www.hongniuzy2.com/api.php/provide/vod', 'active' => true],
    ['key' => 'xinlangaa', 'name' => '新浪资源', 'api' => 'https://api.xinlangapi.com/xinlangapi.php/provide/vod', 'active' => true],
    ['key' => 'ckzy', 'name' => 'CK资源', 'api' => 'https://ckzy.me/api.php/provide/vod', 'active' => true],
    ['key' => 'ukuapi', 'name' => 'U酷资源', 'api' => 'https://api.ukuapi.com/api.php/provide/vod', 'active' => true],
    ['key' => '1080zyk', 'name' => '1080资源', 'api' => 'https://api.1080zyku.com/inc/apijson.php/', 'active' => true],
    ['key' => 'hhzyapi', 'name' => '豪华资源', 'api' => 'https://hhzyapi.com/api.php/provide/vod', 'active' => true],
    ['key' => 'subocaiji', 'name' => '速博资源', 'api' => 'https://subocaiji.com/api.php/provide/vod', 'active' => true],
    ['key' => 'p2100', 'name' => '飘零资源', 'api' => 'https://p2100.net/api.php/provide/vod', 'active' => true],
    ['key' => 'aqyzy', 'name' => '爱奇艺', 'api' => 'https://iqiyizyapi.com/api.php/provide/vod', 'active' => true],
    ['key' => 'yzzy', 'name' => '优质资源', 'api' => 'https://api.yzzy-api.com/inc/apijson.php', 'active' => true],
    ['key' => 'myzy', 'name' => '猫眼资源', 'api' => 'https://api.maoyanapi.top/api.php/provide/vod', 'active' => true],
    ['key' => 'rycj', 'name' => '如意资源', 'api' => 'https://cj.rycjapi.com/api.php/provide/vod', 'active' => true],
    ['key' => 'jinyingzy', 'name' => '金鹰点播', 'api' => 'https://jinyingzy.com/api.php/provide/vod', 'active' => true],
    ['key' => 'guangsuapi', 'name' => '光速资源', 'api' => 'https://api.guangsuapi.com/api.php/provide/vod', 'active' => true]
];

function initDB($DATA_FILE, $DEFAULT_SITES) {
    if (!file_exists($DATA_FILE)) {
        file_put_contents($DATA_FILE, json_encode(['sites' => $DEFAULT_SITES], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
}

function getDB($DATA_FILE, $DEFAULT_SITES, $FORCE_UPDATE) {
    initDB($DATA_FILE, $DEFAULT_SITES);
    
    try {
        $data = json_decode(file_get_contents($DATA_FILE), true);
        
        if ($FORCE_UPDATE) {
            $dbSites = $data['sites'] ?? [];
            foreach ($DEFAULT_SITES as $defSite) {
                $found = false;
                foreach ($dbSites as $site) {
                    if ($site['key'] === $defSite['key']) {
                        $found = true;
                        break;
                    }
                }
                if (!$found) {
                    $dbSites[] = $defSite;
                }
            }
            return ['sites' => $dbSites];
        }
        
        return $data;
    } catch (Exception $e) {
        return ['sites' => $DEFAULT_SITES];
    }
}

function saveDB($DATA_FILE, $data) {
    file_put_contents($DATA_FILE, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

function httpRequest($url, $timeout = 3000) {
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT_MS => $timeout,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error || $httpCode !== 200) {
        return null;
    }
    
    return $response;
}

function getUrlPath() {
    $requestUri = $_SERVER['REQUEST_URI'];
    $path = parse_url($requestUri, PHP_URL_PATH);
    $path = preg_replace('#^/api/#', '', $path);
    return $path;
}

$db = getDB($DATA_FILE, $DEFAULT_SITES, $FORCE_UPDATE);
$path = getUrlPath();

switch (true) {
    case strpos($path, 'tmdbimg') === 0 && $_SERVER['REQUEST_METHOD'] === 'GET':
        $imgPath = substr($path, 7);
        header('Location: ' . $TMDB_PROXY_URL . $imgPath);
        exit;
        
    case strpos($path, 'tmdb') === 0 && $_SERVER['REQUEST_METHOD'] === 'GET':
        $tmdbPath = substr($path, 4);
        $query = $_SERVER['QUERY_STRING'] ?? '';
        
        if ($query) {
            $url = $TMDB_PROXY_URL . $tmdbPath . '?' . $query . '&api_key=' . $TMDB_API_KEY;
        } else {
            $url = $TMDB_PROXY_URL . $tmdbPath . '?api_key=' . $TMDB_API_KEY;
        }
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT_MS => 5000,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($response && $httpCode === 200) {
            echo $response;
        } else {
            http_response_code(500);
            echo json_encode([
                'error' => 'TMDB API Error',
                'url' => $url,
                'httpCode' => $httpCode,
                'curlError' => $error,
                'proxyUrl' => $TMDB_PROXY_URL,
                'apiKey' => substr($TMDB_API_KEY, 0, 8) . '...'
            ], JSON_UNESCAPED_UNICODE);
        }
        break;
        
    case $path === 'check' && $_SERVER['REQUEST_METHOD'] === 'GET':
        $key = $_GET['key'] ?? '';
        $site = null;
        
        foreach ($db['sites'] as $s) {
            if ($s['key'] === $key) {
                $site = $s;
                break;
            }
        }
        
        if (!$site) {
            echo json_encode(['latency' => 9999]);
            break;
        }
        
        $start = microtime(true);
        $response = httpRequest($site['api'] . '?ac=list&pg=1', 3000);
        $latency = (int)((microtime(true) - $start) * 1000);
        
        echo json_encode(['latency' => $response ? $latency : 9999]);
        break;
        
    case $path === 'hot' && $_SERVER['REQUEST_METHOD'] === 'GET':
        $hotKeys = ['ffzy', 'bfzy', 'lzi', 'dbzy'];
        $hotSites = array_filter($db['sites'], function($s) use ($hotKeys) {
            return in_array($s['key'], $hotKeys);
        });
        
        foreach ($hotSites as $site) {
            $response = httpRequest($site['api'] . '?ac=list&pg=1&h=24&out=json', 3000);
            
            if ($response) {
                $data = json_decode($response, true);
                $list = $data['list'] ?? $data['data'] ?? null;
                
                if ($list && is_array($list) && count($list) > 0) {
                    echo json_encode(['list' => array_slice($list, 0, 12)], JSON_UNESCAPED_UNICODE);
                    break 2;
                }
            }
        }
        
        echo json_encode(['list' => []]);
        break;
        
    case $path === 'search' && $_SERVER['REQUEST_METHOD'] === 'GET':
        $wd = $_GET['wd'] ?? '';
        
        if (!$wd) {
            echo json_encode(['list' => []]);
            break;
        }
        
        $activeSites = array_filter($db['sites'], function($s) {
            return $s['active'] ?? false;
        });
        
        $results = [];
        $mh = curl_multi_init();
        $handles = [];
        $siteMap = [];
        
        foreach ($activeSites as $index => $site) {
            $url = $site['api'] . '?ac=list&wd=' . urlencode($wd) . '&out=json';
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT_MS => 6000,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
            ]);
            
            curl_multi_add_handle($mh, $ch);
            $handles[$index] = $ch;
            $siteMap[$index] = $site;
        }
        
        $running = null;
        do {
            curl_multi_exec($mh, $running);
            curl_multi_select($mh);
        } while ($running > 0);
        
        foreach ($handles as $index => $ch) {
            $response = curl_multi_getcontent($ch);
            $site = $siteMap[$index];
            
            if ($response) {
                $data = json_decode($response, true);
                $list = $data['list'] ?? $data['data'] ?? null;
                
                if ($list && is_array($list)) {
                    foreach ($list as $item) {
                        $item['site_key'] = $site['key'];
                        $item['site_name'] = $site['name'];
                        $item['latency'] = 0;
                        $results[] = $item;
                    }
                }
            }
            
            curl_multi_remove_handle($mh, $ch);
            curl_close($ch);
        }
        
        curl_multi_close($mh);
        echo json_encode(['list' => $results], JSON_UNESCAPED_UNICODE);
        break;
        
    case $path === 'detail' && $_SERVER['REQUEST_METHOD'] === 'GET':
        $siteKey = $_GET['site_key'] ?? '';
        $id = $_GET['id'] ?? '';
        
        $targetSite = null;
        foreach ($db['sites'] as $s) {
            if ($s['key'] === $siteKey) {
                $targetSite = $s;
                break;
            }
        }
        
        if (!$targetSite) {
            http_response_code(404);
            echo json_encode(['error' => 'Site not found']);
            break;
        }
        
        $response = httpRequest($targetSite['api'] . '?ac=detail&ids=' . $id . '&out=json', 6000);
        
        if ($response) {
            echo $response;
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Source Error']);
        }
        break;
        
    case $path === 'admin/login' && $_SERVER['REQUEST_METHOD'] === 'POST':
        $input = json_decode(file_get_contents('php://input'), true);
        $password = $input['password'] ?? '';
        
        if ($password === $ADMIN_PASSWORD) {
            echo json_encode(['success' => true]);
        } else {
            http_response_code(403);
            echo json_encode(['success' => false, 'msg' => '密码错误']);
        }
        break;
        
    case $path === 'admin/sites' && $_SERVER['REQUEST_METHOD'] === 'GET':
        echo json_encode($db['sites'], JSON_UNESCAPED_UNICODE);
        break;
        
    case $path === 'admin/sites' && $_SERVER['REQUEST_METHOD'] === 'POST':
        $input = json_decode(file_get_contents('php://input'), true);
        saveDB($DATA_FILE, ['sites' => $input['sites'] ?? []]);
        echo json_encode(['success' => true]);
        break;
        
    default:
        http_response_code(404);
        echo json_encode(['error' => 'Not found']);
        break;
}
