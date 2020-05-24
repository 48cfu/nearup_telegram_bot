<?

namespace Near;

use Settings\Config;
use PDO;

Class NearData
{
    public static function GetNearRpcData($method, $params = [null])
    {
        $message = json_encode(array('jsonrpc' => '2.0', 'id' => "dontcare", 'method' => $method, 'params' => $params));
        $requestHeaders = ['Content-type: application/json'];

        $ch = curl_init("https://rpc.betanet.nearprotocol.com");
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $message);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $requestHeaders);

        $json = curl_exec($ch);
        curl_close($ch);

        $data = json_decode($json, true);
        return $data;
    }

    public static function GetAccountAccessKeys($account)
    {
        return NearData::GetNearRpcData("query", ["request_type" => "view_access_key_list", "finality" => "final", "account_id" => $account]);
    }

    public static function GetAccountBalance($account)
    {
        return NearData::GetNearRpcData("query", ["request_type" => "view_account", "finality" => "final", "account_id" => $account]);
    }

    public static function RoundNearBalance($amount)
    {
        return (round($amount / 1000000000000000000000000));
    }

    public static function ConvertNearToYoctoNear($amount)
    {
        if(intval($amount) > 0)
            return $amount . "000000000000000000000000";
        else
            return "";
    }


    public static function InitializeDB($table_prefix = null, $encoding = 'utf8mb4')
    {
        $credentials = Config::$mysql_credentials;
        if (empty($credentials)) {
            throw new TelegramException('MySQL credentials not provided!');
        }

        $dsn = 'mysql:host=' . $credentials['host'] . ';dbname=' . $credentials['database'];
        if (!empty($credentials['port'])) {
            $dsn .= ';port=' . $credentials['port'];
        }

        $options = [PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES ' . $encoding];
        try {
            $pdo = new PDO($dsn, $credentials['user'], $credentials['password'], $options);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);
            return $pdo;
        } catch (PDOException $e) {
            throw new TelegramException($e->getMessage());
        }
    }

    public static function GetUserLogin($pdo, $user_id)
    {
        try {
            $sth = $pdo->prepare('SELECT `near_account` FROM `user` WHERE `id` = :user_id LIMIT 1');

            $sth->bindValue(':user_id', $user_id);

            $sth->execute();

            return $sth->fetchColumn(0);
        } catch (Exception $e) {
            throw new TelegramException($e->getMessage());
        }
    }

    public static function SetUserCredentials($pdo, $id, $near_account, $public_key, $private_key)
    {
        try {
            $sth = $pdo->prepare('
                UPDATE user SET
                `near_account` = :near_account, `public_key` = :public_key, `private_key`= :private_key, `key_added_at` = NOW()
                WHERE id = :id
                LIMIT 1
            ');

            $sth->bindValue(':id', $id);
            $sth->bindValue(':near_account', $near_account);
            $sth->bindValue(':public_key', $public_key);
            $sth->bindValue(':private_key', $private_key);

            $status = $sth->execute();
        } catch (Exception $e) {
            throw new TelegramException($e->getMessage());
        }
        return $status;
    }

    public static function GetPrivateKey($pdo, $user_id)
    {
        try {
            $sth = $pdo->prepare('SELECT `private_key` FROM `user` WHERE `id` = :user_id LIMIT 1');

            $sth->bindValue(':user_id', $user_id);

            $sth->execute();

            return $sth->fetchColumn(0);
        } catch (Exception $e) {
            throw new TelegramException($e->getMessage());
        }
    }

    public static function GetPublicKey($pdo, $user_id)
    {
        try {
            $sth = $pdo->prepare('SELECT `public_key` FROM `user` WHERE `id` = :user_id LIMIT 1');

            $sth->bindValue(':user_id', $user_id);

            $sth->execute();

            return $sth->fetchColumn(0);
        } catch (Exception $e) {
            throw new TelegramException($e->getMessage());
        }
    }

    public static function saveUserDetails($pdo, $user_id, $near_account, $public_key, $private_key)
    {
        try {
            $sth = $pdo->prepare('UPDATE `user` SET `near_account` = :near_account, `public_key` = :public_key, `private_key`=:private_key, `key_added_at` = NOW()
                WHERE `id` = :user_id LIMIT 1');

            $sth->bindValue(':user_id', $user_id);
            $sth->bindValue(':near_account', $near_account);
            $sth->bindValue(':public_key', $public_key);
            $sth->bindValue(':private_key', $private_key);

            return $sth->execute();
        } catch (Exception $e) {
            throw new TelegramException($e->getMessage());
        }
    }
}

Class NearView
{
    public static function FormatValidators($validators, $strings)
    {
        if (!is_array($validators) || !count($validators))
            return $strings["notFound"];

        usort($validators, function ($a, $b) {
            return strcmp($a["account_id"], $b["account_id"]);
        });

        $reply = "";
        $i = 1;
        $alertFound = false;
        $validatorPositionsQuantity = strlen(count($validators));

        foreach ($validators as $validator) {
            $alert = "";
            if (isset($validator["num_produced_blocks"]) && isset($validator["num_expected_blocks"]) && $validator["num_produced_blocks"] < ($validator["num_expected_blocks"] * 0.9)) {
                $alert = " ⚠️ `(" . $validator["num_produced_blocks"] . "/" . $validator["num_expected_blocks"] . ")`";
                $alertFound = true;
            }
            $reply .= (str_pad($i++, $validatorPositionsQuantity, "0", STR_PAD_LEFT)) . ". " . NearView::EscapeMarkdownCharacters($validator["account_id"]) . ": `" . NearData::RoundNearBalance($validator["stake"]) . " NEAR`" . $alert . "\n";
        }

        if ($alertFound)
            $reply .= $strings['validatorsLegend'];

        return $reply;
    }

    public static function EscapeMarkdownCharacters($string){
        $string = str_replace("_", "\_", $string);
        return $string;
    }

    public static function GetAccountDetails($account){
        if ($account) {
            $accountData = NearData::GetAccountBalance($account);

            if (isset($accountData["error"]))
                $reply = $accountData["error"]["message"] . " " . $accountData["error"]["data"];
            else {
                $reply = NearView::GetAccountDataDetails($account, $accountData["result"]);
            }
        }
        else
            $reply = "Wrong data";

        return $reply;
    }

    public static function GetAccountDataDetails($account, $accountData)
    {
        $output = [
            "Account " . $account,
            "Balance: " . NearData::RoundNearBalance($accountData["amount"]),
            "Locked: " . NearData::RoundNearBalance($accountData["locked"]),
            "Storage Usage: " . NearData::RoundNearBalance($accountData["storage_usage"]),
            "Access Keys List: /ViewAccessKey_" . $account
        ];
        return join(chr(10), $output);
    }

    public static function GetAccountAccessKeysDetails($account){
        if ($account) {
            $accountData = NearData::GetAccountAccessKeys($account);

            if (isset($accountData["error"]))
                $reply = $accountData["error"]["message"] . " " . $accountData["error"]["data"];
            else {
                $output[] = "Account " . $account;
                if (!$accountData["result"]["keys"])
                    $output[] = "[locked account]";
                else {
                    foreach ($accountData["result"]["keys"] as $key) {
                        $output[] = "- " . $key["public_key"] . " (" . $key["access_key"]["permission"] . ", nonce: " . $key["access_key"]["nonce"] . ")";
                    }
                }

                $reply = join(chr(10), $output);
            }
        }
        else
            $reply = "Wrong data";

        return $reply;
    }
}