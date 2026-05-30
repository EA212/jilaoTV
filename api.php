<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

function errorHandler($errno, $errstr, $errfile, $errline) {
    $errorTypes = [
        E_ERROR => 'Error',
        E_WARNING => 'Warning',
        E_PARSE => 'Parse Error',
        E_NOTICE => 'Notice',
        E_CORE_ERROR => 'Core Error',
        E_CORE_WARNING => 'Core Warning',
        E_COMPILE_ERROR => 'Compile Error',
        E_COMPILE_WARNING => 'Compile Warning',
        E_USER_ERROR => 'User Error',
        E_USER_WARNING => 'User Warning',
        E_USER_NOTICE => 'User Notice',
        E_STRICT => 'Strict',
        E_RECOVERABLE_ERROR => 'Recoverable Error',
        E_DEPRECATED => 'Deprecated',
        E_USER_DEPRECATED => 'User Deprecated'
    ];
    
    $type = $errorTypes[$errno] ?? 'Unknown Error';
    
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'error' => true,
        'type' => $type,
        'message' => $errstr,
        'file' => basename($errfile),
        'line' => $errline
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

function exceptionHandler($exception) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'error' => true,
        'type' => 'Exception',
        'message' => $exception->getMessage(),
        'file' => basename($exception->getFile()),
        'line' => $exception->getLine(),
        'trace' => $exception->getTraceAsString()
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

set_error_handler('errorHandler');
set_exception_handler('exceptionHandler');

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$DB_FILE = __DIR__ . '/db.sqlite';

$envFile = __DIR__ . '/.env.php';
$env = file_exists($envFile) ? include($envFile) : [];

$ADMIN_PASSWORD = $env['ADMIN_PASSWORD'] ?? 'admin';
$FORCE_UPDATE = ($env['FORCE_UPDATE'] ?? 'false') === 'true';
$TMDB_API_KEY = $env['TMDB_API_KEY'] ?? '';
$TMDB_PROXY_URL = $env['TMDB_PROXY_URL'] ?? '';

$CACHE_ENABLED = function_exists('apcu_fetch');

$DB_CACHE_CONFIG = [
    'search' => 600,
    'hot' => 1800,
    'detail' => 3600,
    'tmdb' => 3600,
    'check' => 1800
];

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

function getCacheConfig($db, $key, $default) {
    $stmt = $db->prepare('SELECT config_value FROM config WHERE config_key = :key');
    if (!$stmt) return $default;
    
    $stmt->bindValue(':key', 'cache_ttl_' . $key, SQLITE3_TEXT);
    $result = $stmt->execute();
    if (!$result) return $default;
    
    $row = $result->fetchArray();
    
    if ($row) {
        return (int)$row['config_value'];
    }
    
    return $default;
}

function initCacheConfig($db, $defaultConfig) {
    $stmt = $db->prepare('INSERT OR IGNORE INTO config (config_key, config_value) VALUES (:key, :value)');
    if (!$stmt) return;
    
    foreach ($defaultConfig as $key => $value) {
        $stmt->bindValue(':key', 'cache_ttl_' . $key, SQLITE3_TEXT);
        $stmt->bindValue(':value', (string)$value, SQLITE3_TEXT);
        $stmt->execute();
        $stmt->reset();
    }
}

function cacheGet($key, $useSQLite = false) {
    global $CACHE_ENABLED, $db;
    
    if ($useSQLite && isset($db['db'])) {
        $stmt = $db['db']->prepare('SELECT cache_data FROM cache WHERE cache_key = :key AND expire_time > :now');
        if (!$stmt) return null;
        
        $stmt->bindValue(':key', $key, SQLITE3_TEXT);
        $stmt->bindValue(':now', time(), SQLITE3_INTEGER);
        $result = $stmt->execute();
        if (!$result) return null;
        
        $row = $result->fetchArray();
        
        if ($row) {
            return $row['cache_data'];
        }
        return null;
    }
    
    if (!$CACHE_ENABLED) return null;
    
    $success = false;
    $data = apcu_fetch($key, $success);
    return $success ? $data : null;
}

function cacheSet($key, $data, $ttl, $useSQLite = false) {
    global $CACHE_ENABLED, $db;
    
    if ($useSQLite && isset($db['db'])) {
        $expireTime = time() + $ttl;
        
        $stmt = $db['db']->prepare('INSERT OR REPLACE INTO cache (cache_key, cache_data, expire_time) VALUES (:key, :data, :expire)');
        if (!$stmt) return false;
        
        $stmt->bindValue(':key', $key, SQLITE3_TEXT);
        $stmt->bindValue(':data', $data, SQLITE3_TEXT);
        $stmt->bindValue(':expire', $expireTime, SQLITE3_INTEGER);
        $stmt->execute();
        
        return true;
    }
    
    if (!$CACHE_ENABLED) return false;
    
    return apcu_store($key, $data, $ttl);
}

function cacheClean($db) {
    $db->exec('DELETE FROM cache WHERE expire_time <= ' . time());
}

function initSQLite($DB_FILE) {
    $db = new SQLite3($DB_FILE);
    
    $db->exec('CREATE TABLE IF NOT EXISTS sites (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        site_key TEXT UNIQUE NOT NULL,
        site_name TEXT NOT NULL,
        api_url TEXT NOT NULL,
        is_active INTEGER DEFAULT 1
    )');
    
    $db->exec('CREATE TABLE IF NOT EXISTS cache (
        cache_key TEXT PRIMARY KEY,
        cache_data TEXT NOT NULL,
        expire_time INTEGER NOT NULL,
        created_at INTEGER DEFAULT (strftime(\'%s\', \'now\'))
    )');
    
    $db->exec('CREATE INDEX IF NOT EXISTS idx_cache_expire ON cache(expire_time)');
    
    $db->exec('CREATE TABLE IF NOT EXISTS config (
        config_key TEXT PRIMARY KEY,
        config_value TEXT NOT NULL
    )');
    
    return $db;
}

function initDB($DB_FILE, $DEFAULT_SITES) {
    $db = initSQLite($DB_FILE);
    if (!$db) return null;
    
    $count = $db->querySingle('SELECT COUNT(*) FROM sites');
    
    if ($count == 0) {
        $stmt = $db->prepare('INSERT INTO sites (site_key, site_name, api_url, is_active) VALUES (:key, :name, :api, :active)');
        if ($stmt) {
            foreach ($DEFAULT_SITES as $site) {
                $stmt->bindValue(':key', $site['key'], SQLITE3_TEXT);
                $stmt->bindValue(':name', $site['name'], SQLITE3_TEXT);
                $stmt->bindValue(':api', $site['api'], SQLITE3_TEXT);
                $stmt->bindValue(':active', $site['active'] ? 1 : 0, SQLITE3_INTEGER);
                $stmt->execute();
                $stmt->reset();
            }
        }
    }
    
    return $db;
}

function getDB($DB_FILE, $DEFAULT_SITES, $FORCE_UPDATE, $DB_CACHE_CONFIG) {
    $db = initDB($DB_FILE, $DEFAULT_SITES);
    
    if (!$db) {
        return [
            'db' => null,
            'sites' => $DEFAULT_SITES,
            'cacheConfig' => $DB_CACHE_CONFIG
        ];
    }
    
    initCacheConfig($db, $DB_CACHE_CONFIG);
    
    if ($FORCE_UPDATE) {
        $existingKeys = [];
        $result = $db->query('SELECT site_key FROM sites');
        while ($row = $result->fetchArray()) {
            $existingKeys[] = $row['site_key'];
        }
        
        $stmt = $db->prepare('INSERT OR IGNORE INTO sites (site_key, site_name, api_url, is_active) VALUES (:key, :name, :api, :active)');
        if ($stmt) {
            foreach ($DEFAULT_SITES as $site) {
                if (!in_array($site['key'], $existingKeys)) {
                    $stmt->bindValue(':key', $site['key'], SQLITE3_TEXT);
                    $stmt->bindValue(':name', $site['name'], SQLITE3_TEXT);
                    $stmt->bindValue(':api', $site['api'], SQLITE3_TEXT);
                    $stmt->bindValue(':active', $site['active'] ? 1 : 0, SQLITE3_INTEGER);
                    $stmt->execute();
                    $stmt->reset();
                }
            }
        }
    }
    
    $sites = [];
    $result = $db->query('SELECT site_key, site_name, api_url, is_active FROM sites');
    while ($row = $result->fetchArray()) {
        $sites[] = [
            'key' => $row['site_key'],
            'name' => $row['site_name'],
            'api' => $row['api_url'],
            'active' => (bool)$row['is_active']
        ];
    }
    
    $cacheConfig = [];
    foreach ($DB_CACHE_CONFIG as $key => $default) {
        $cacheConfig[$key] = getCacheConfig($db, $key, $default);
    }
    
    cacheClean($db);
    
    return ['db' => $db, 'sites' => $sites, 'cacheConfig' => $cacheConfig];
}

function saveDB($DB_FILE, $sites) {
    $db = initSQLite($DB_FILE);
    if (!$db) return false;
    
    $db->exec('BEGIN TRANSACTION');
    $db->exec('DELETE FROM sites');
    
    $stmt = $db->prepare('INSERT INTO sites (site_key, site_name, api_url, is_active) VALUES (:key, :name, :api, :active)');
    if ($stmt) {
        foreach ($sites as $site) {
            $stmt->bindValue(':key', $site['key'], SQLITE3_TEXT);
            $stmt->bindValue(':name', $site['name'], SQLITE3_TEXT);
            $stmt->bindValue(':api', $site['api'], SQLITE3_TEXT);
            $stmt->bindValue(':active', $site['active'] ? 1 : 0, SQLITE3_INTEGER);
            $stmt->execute();
            $stmt->reset();
        }
    }
    
    $db->exec('COMMIT');
    return true;
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

$db = getDB($DB_FILE, $DEFAULT_SITES, $FORCE_UPDATE, $DB_CACHE_CONFIG);
$path = getUrlPath();

$CACHE_TTL_SEARCH = $db['cacheConfig']['search'];
$CACHE_TTL_HOT = $db['cacheConfig']['hot'];
$CACHE_TTL_DETAIL = $db['cacheConfig']['detail'];
$CACHE_TTL_TMDB = $db['cacheConfig']['tmdb'];
$CACHE_TTL_CHECK = $db['cacheConfig']['check'];

switch (true) {
    case strpos($path, 'tmdbimg') === 0 && $_SERVER['REQUEST_METHOD'] === 'GET':
        $imgPath = substr($path, 7);
        header('Location: ' . $TMDB_PROXY_URL . $imgPath);
        exit;
        
    case strpos($path, 'tmdb') === 0 && $_SERVER['REQUEST_METHOD'] === 'GET':
        $tmdbPath = substr($path, 4);
        $query = $_SERVER['QUERY_STRING'] ?? '';
        
        $cacheKey = 'tmdb:' . $tmdbPath . ':' . md5($query);
        $cached = cacheGet($cacheKey, true);
        
        if ($cached !== null) {
            echo $cached;
            break;
        }
        
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
            cacheSet($cacheKey, $response, $CACHE_TTL_TMDB, true);
            echo $response;
        } else {
            http_response_code(500);
            echo json_encode([
                'error' => 'TMDB API Error',
                'httpCode' => $httpCode,
                'curlError' => $error
            ], JSON_UNESCAPED_UNICODE);
        }
        break;
        
    case $path === 'check' && $_SERVER['REQUEST_METHOD'] === 'GET':
        $key = $_GET['key'] ?? '';
        
        $cacheKey = 'check:' . $key;
        $cached = cacheGet($cacheKey);
        
        if ($cached !== null) {
            echo json_encode($cached);
            break;
        }
        
        $site = null;
        
        foreach ($db['sites'] as $s) {
            if ($s['key'] === $key) {
                $site = $s;
                break;
            }
        }
        
        if (!$site) {
            $result = ['latency' => 9999];
            cacheSet($cacheKey, $result, $CACHE_TTL_CHECK);
            echo json_encode($result);
            break;
        }
        
        $start = microtime(true);
        $response = httpRequest($site['api'] . '?ac=list&pg=1', 3000);
        $latency = (int)((microtime(true) - $start) * 1000);
        
        $result = ['latency' => $response ? $latency : 9999];
        cacheSet($cacheKey, $result, $CACHE_TTL_CHECK);
        echo json_encode($result);
        break;
        
    case $path === 'hot' && $_SERVER['REQUEST_METHOD'] === 'GET':
        $cacheKey = 'hot:list';
        $cached = cacheGet($cacheKey);
        
        if ($cached !== null) {
            echo json_encode($cached);
            break;
        }
        
        $hotKeys = ['ffzy', 'bfzy', 'lzi', 'dbzy'];
        $hotSites = array_filter($db['sites'], function($s) use ($hotKeys) {
            return in_array($s['key'], $hotKeys);
        });
        
        $result = ['list' => []];
        
        foreach ($hotSites as $site) {
            $response = httpRequest($site['api'] . '?ac=list&pg=1&h=24&out=json', 3000);
            
            if ($response) {
                $data = json_decode($response, true);
                $list = $data['list'] ?? $data['data'] ?? null;
                
                if ($list && is_array($list) && count($list) > 0) {
                    $result = ['list' => array_slice($list, 0, 12)];
                    break;
                }
            }
        }
        
        cacheSet($cacheKey, $result, $CACHE_TTL_HOT);
        echo json_encode($result, JSON_UNESCAPED_UNICODE);
        break;
        
    case $path === 'search' && $_SERVER['REQUEST_METHOD'] === 'GET':
        $wd = $_GET['wd'] ?? '';
        
        if (!$wd) {
            echo json_encode(['list' => []]);
            break;
        }
        
        $cacheKey = 'search:' . md5($wd);
        $cached = cacheGet($cacheKey, true);
        
        if ($cached !== null) {
            echo $cached;
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
        
        $result = ['list' => $results];
        $resultJson = json_encode($result, JSON_UNESCAPED_UNICODE);
        cacheSet($cacheKey, $resultJson, $CACHE_TTL_SEARCH, true);
        echo $resultJson;
        break;
        
    case $path === 'detail' && $_SERVER['REQUEST_METHOD'] === 'GET':
        $siteKey = $_GET['site_key'] ?? '';
        $id = $_GET['id'] ?? '';
        
        $cacheKey = 'detail:' . $siteKey . ':' . $id;
        $cached = cacheGet($cacheKey, true);
        
        if ($cached !== null) {
            echo $cached;
            break;
        }
        
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
            cacheSet($cacheKey, $response, $CACHE_TTL_DETAIL, true);
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
        saveDB($DB_FILE, $input['sites'] ?? []);
        echo json_encode(['success' => true]);
        break;
        
    case $path === 'admin/cache/config' && $_SERVER['REQUEST_METHOD'] === 'GET':
        echo json_encode($db['cacheConfig'] ?? [], JSON_UNESCAPED_UNICODE);
        break;
        
    case $path === 'admin/cache/config' && $_SERVER['REQUEST_METHOD'] === 'POST':
        $input = json_decode(file_get_contents('php://input'), true);
        
        $dbObj = $db['db'] ?? null;
        if (!$dbObj) {
            echo json_encode(['error' => 'Database not available']);
            break;
        }
        
        $dbObj->exec('BEGIN TRANSACTION');
        
        foreach ($input as $key => $value) {
            $stmt = $dbObj->prepare('INSERT OR REPLACE INTO config (config_key, config_value) VALUES (:key, :value)');
            if ($stmt) {
                $stmt->bindValue(':key', 'cache_ttl_' . $key, SQLITE3_TEXT);
                $stmt->bindValue(':value', (string)$value, SQLITE3_TEXT);
                $stmt->execute();
            }
        }
        
        $dbObj->exec('COMMIT');
        echo json_encode(['success' => true]);
        break;
        
    case $path === 'admin/cache/clear' && $_SERVER['REQUEST_METHOD'] === 'POST':
        $dbObj = $db['db'] ?? null;
        if ($dbObj) {
            $dbObj->exec('DELETE FROM cache');
        }
        
        if ($CACHE_ENABLED) {
            apcu_clear_cache();
        }
        
        echo json_encode(['success' => true]);
        break;
        
    case $path === 'admin/cache/stats' && $_SERVER['REQUEST_METHOD'] === 'GET':
        $dbObj = $db['db'] ?? null;
        
        $sqliteCount = 0;
        $sqliteSize = 0;
        
        if ($dbObj) {
            $sqliteCount = $dbObj->querySingle('SELECT COUNT(*) FROM cache') ?: 0;
            $sqliteSize = $dbObj->querySingle('SELECT SUM(LENGTH(cache_data)) FROM cache') ?: 0;
        }
        
        $apcuStats = null;
        if ($CACHE_ENABLED && function_exists('apcu_cache_info')) {
            $apcuInfo = apcu_cache_info();
            $apcuStats = [
                'num_entries' => $apcuInfo['num_entries'] ?? 0,
                'mem_size' => $apcuInfo['mem_size'] ?? 0,
                'hits' => $apcuInfo['num_hits'] ?? 0,
                'misses' => $apcuInfo['num_misses'] ?? 0
            ];
        }
        
        echo json_encode([
            'sqlite' => [
                'count' => $sqliteCount,
                'size' => $sqliteSize,
                'size_mb' => round($sqliteSize / 1024 / 1024, 2)
            ],
            'apcu' => $apcuStats,
            'apcu_enabled' => $CACHE_ENABLED
        ], JSON_UNESCAPED_UNICODE);
        break;
        
    default:
        http_response_code(404);
        echo json_encode(['error' => 'Not found']);
        break;
}
