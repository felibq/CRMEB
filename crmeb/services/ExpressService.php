<?php
/**
 *
 * @author: xaboy<365615158@qq.com>
 * @day: 2018/01/10
 */

namespace crmeb\services;

use think\facade\Request;
use crmeb\services\HttpService;
use crmeb\services\SystemConfigService;

class ExpressService
{
    protected static $api = [
        'query' => '/api/express/search'
    ];

    public static function query($no, $type = '')
    {
        // $appCode = SystemConfigService::config('system_express_app_code');
        // if (!$appCode) return false;
        $domain = Request::domain(1);
        $res = HttpService::postRequest($domain.self::$api['query'], compact('no', 'type'));
        $result = json_decode($res, true) ?: false;
        return $result;
    }

}