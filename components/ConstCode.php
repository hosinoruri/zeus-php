<?php
namespace app\components;

class ConstCode
{
    /**
     * 根据常量的前缀获取键值对数组
     * @param string $key 常量下标  例：如果需要用户状态文字说明，只需要传AD_ACCOUNT_STATUS常量的开头字符作为标识
     * @throws
     * @return array
     */
    public static function getConstDesc($key) {
        $oClass = new \ReflectionClass(static::class);
        $constAry = $oClass->getConstants();
        $desc = [];
        if (is_array($constAry))
        {
            foreach ( $constAry as $const => $v ) {
                if ( strpos($const, $key ) === 0 ) {
                    if (is_array($v))
                    {
                        $desc[$v[0]] = $v[1];
                    } else {
                        $desc[] = $v;
                    }
                }
            }
        }
        return $desc;
    }
}