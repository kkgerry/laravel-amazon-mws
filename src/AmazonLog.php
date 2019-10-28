<?php
class AmazonLog
{
    static function info($message)
    {
        // do nothing
        self::RecordLog($message,'info');
    }
    static function notice($message)
    {
        // do nothing
        self::RecordLog($message,'notice');
    }
    static function warning($message)
    {
        // do nothing
        self::RecordLog($message,'warning');
    }
    static function error($message)
    {
        self::RecordLog($message,'error');
        // do nothing
    }

    static function RecordLog($message,$type='info')
    {
        if(!empty($message)) {
            $path = storage_path('logs') . '/amazon/' . date("Y-m-d") . '.log';
            $content = date('Y-m-d H:i:s') . ' ';
            $content .= 'Amazon ['.$type.']: ' . $message. "\r\n";
            file_put_contents($path, $content, FILE_APPEND);
        }
    }
}