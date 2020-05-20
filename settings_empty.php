<?

namespace Settings;

Class Config
{
    public static $bot_api_key = '';
    public static $bot_username = '';
    public static $hook_url = '/hook.php';
    public static $nodejs_folder = '';
    public static $nearAccountDomain = "";
    public static $restrictedChatIds = [];
    public static $adminIds = [];

    public static $mysql_credentials = [
        'host' => '',
        'port' => 3306,
        'user' => '',
        'password' =>  '',
        'database' => ''
    ];

}