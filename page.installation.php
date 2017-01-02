<?php
include "module.utils.php";
include "module.crypto.php";

error_reporting(E_ERROR | E_PARSE);

$pageTitle = "단상 설치하기";
$pageAuthor = "단상";
$pageResponse = response();

function response() {

    global $module;

    // 데이터베이스 설치 파라미터가 들어왔을 경우
    if (!isset($_POST["db-host"])) {
        return array("type" => "success");
    }

    if (!isset($_POST["db-name"]) || !isset($_POST["db-id"])
        || !isset($_POST["db-password"]) || !isset($_POST["db-table-prefix"])
        || !isset($_POST["db-timezone"]) || !isset($_POST["dansang-password"])) {
        return array("type" => "error",
                     "message" => "파라미터가 충분하지 않습니다.");
    }

    // 데이터베이스 접속 테스트
    $db = new mysqli($_POST["db-host"], $_POST["db-id"], $_POST["db-password"], $_POST["db-name"]);
    $db->set_charset("utf8");
    if ($db->connect_error) {
        return array("type" => "error",
                     "message" => "데이터베이스 접속에 실패했습니다.");
    }

    // prefix 유효성 체크
    $tablePrefix = preg_replace('/\s+/', '', $_POST["db-table-prefix"]);

    // 테이블 생성
    if (!file_exists("./sql/articles.sql") || !file_exists("./sql/settings.sql")) {
        return array("type" => "error",
                     "message" => "데이터베이스 테이블 생성 쿼리가 존재하지 않습니다.");
    }

    $articlesSql = str_replace("@", $tablePrefix, file_get_contents("./sql/articles.sql")); 
    $settingsSql = str_replace("@", $tablePrefix, file_get_contents("./sql/settings.sql")); 

    $articlesSqlResult = $db->query($articlesSql);
    $settingsSqlResult = $db->query($settingsSql);

    if (!$articlesSqlResult || !$settingsSqlResult) {
        return array("type" => "error",
                     "message" => "데이터베이스 테이블 생성에 실패하였습니다.");
    }

    // 각종 설정 데이터 추가
    $passwordSql = "INSERT INTO `".$tablePrefix."dansang_settings` (`key`, `value`) VALUES ('password', '".$module->crypto->hash($_POST["dansang-password"])."');";
    $timezoneSql = "INSERT INTO `".$tablePrefix."dansang_settings` (`key`, `value`) VALUES ('timezone', '".$_POST["db-timezone"]."');";

    $passwordSqlResult = $db->query($passwordSql);
    $timezoneSqlResult = $db->query($timezoneSql);

    if (!$passwordSqlResult || !$timezoneSqlResult) {
        return array("type" => "error",
                     "message" => "데이터베이스 테이블 설정에 실패하였습니다.");
    }

    // 설정 파일 쓰기
    $config = "<?php\n";
    $config .= "const SERVER_NAME = \"" .$_POST["db-host"]. "\";\n";
    $config .= "const USER_NAME = \"" .$_POST["db-id"]. "\";\n";
    $config .= "const USER_PASSWORD = \"" .$_POST["db-password"]. "\";\n";
    $config .= "const DB_NAME = \"" .$_POST["db-name"]. "\";\n";
    $config .= "const TABLE_PREFIX = \"" .$tablePrefix. "\";\n";
    $config .= "?>";

    $fileWriteResult = file_put_contents("./module.db.account.php", $config);

    if (!$fileWriteResult) {
        return array("type" => "error",
                     "message" => "설정 파일 생성에 실패하였습니다. 폴더 권한을 확인해 주세요.");
    }

    header("Location: ./?page=articles");

    return array("type" => "success");
}

include "frame.header.php";
?>
<div class="container" style="margin-top: 10%">
    <h4><a href="#">단상 · 斷想<br/>설치를 시작합니다.</a></h4>
    <?php
    if ($pageResponse["type"] == "success") {
        echo("<p>당신의 삶이 기록되는데 필요한 정보를 알려주세요.</p>");
    } else {
        echo("<p style=\"color: #DC143C\">" . $pageResponse["message"] . "</p>");
    }
    ?>
    <hr>
    <form method="post">
        <div class="row">
            <div class="one-half column">
                <label for="dbHostInput">데이터베이스 호스트</label>
                <input class="u-full-width" type="text" placeholder="localhost" id="dbHostInput" name="db-host" <?php $module->utils->defaultPostValue("db-host");?> required>
            </div>
            <div class="one-half column">
                <label for="dbNameInput">데이터베이스 이름</label>
                <input class="u-full-width" type="text" id="dbNameInput" name="db-name" <?php $module->utils->defaultPostValue("db-name");?> required>
            </div>
        </div>
       <div class="row">
            <div class="one-half column">
                <label for="dbIdInput">데이터베이스 아이디</label>
                <input class="u-full-width" type="text"  id="dbIdInput" name="db-id" <?php $module->utils->defaultPostValue("db-id");?> required>
            </div>
            <div class="one-half column">
                <label for="dbPasswordInput">데이터베이스 비밀번호</label>
                <input class="u-full-width" type="text" id="dbPasswordInput" name="db-password" <?php $module->utils->defaultPostValue("db-password");?> required>
            </div>
        </div>
        <label for="dbTablePrefixInput">테이블 이름 접두사</label>
        <input class="u-full-width" type="text" id="dbTablePrefixInput" name="db-table-prefix" <?php $module->utils->defaultPostValue("db-table-prefix");?> required>
        <label for="dbTimezoneInput">당신이 위치한 지역</label>
        <select class="u-full-width" id="dbTimezoneInput" name="db-timezone">
            <option value="Pacific/Midway">(GMT-11:00) Midway Island, Samoa</option>
            <option value="America/Adak">(GMT-10:00) Hawaii-Aleutian</option>
            <option value="Etc/GMT+10">(GMT-10:00) Hawaii</option>
            <option value="Pacific/Marquesas">(GMT-09:30) Marquesas Islands</option>
            <option value="Pacific/Gambier">(GMT-09:00) Gambier Islands</option>
            <option value="America/Anchorage">(GMT-09:00) Alaska</option>
            <option value="America/Ensenada">(GMT-08:00) Tijuana, Baja California</option>
            <option value="Etc/GMT+8">(GMT-08:00) Pitcairn Islands</option>
            <option value="America/Los_Angeles">(GMT-08:00) Pacific Time (US & Canada)</option>
            <option value="America/Denver">(GMT-07:00) Mountain Time (US & Canada)</option>
            <option value="America/Chihuahua">(GMT-07:00) Chihuahua, La Paz, Mazatlan</option>
            <option value="America/Dawson_Creek">(GMT-07:00) Arizona</option>
            <option value="America/Belize">(GMT-06:00) Saskatchewan, Central America</option>
            <option value="America/Cancun">(GMT-06:00) Guadalajara, Mexico City, Monterrey</option>
            <option value="Chile/EasterIsland">(GMT-06:00) Easter Island</option>
            <option value="America/Chicago">(GMT-06:00) Central Time (US & Canada)</option>
            <option value="America/New_York">(GMT-05:00) Eastern Time (US & Canada)</option>
            <option value="America/Havana">(GMT-05:00) Cuba</option>
            <option value="America/Bogota">(GMT-05:00) Bogota, Lima, Quito, Rio Branco</option>
            <option value="America/Caracas">(GMT-04:30) Caracas</option>
            <option value="America/Santiago">(GMT-04:00) Santiago</option>
            <option value="America/La_Paz">(GMT-04:00) La Paz</option>
            <option value="Atlantic/Stanley">(GMT-04:00) Faukland Islands</option>
            <option value="America/Campo_Grande">(GMT-04:00) Brazil</option>
            <option value="America/Goose_Bay">(GMT-04:00) Atlantic Time (Goose Bay)</option>
            <option value="America/Glace_Bay">(GMT-04:00) Atlantic Time (Canada)</option>
            <option value="America/St_Johns">(GMT-03:30) Newfoundland</option>
            <option value="America/Araguaina">(GMT-03:00) UTC-3</option>
            <option value="America/Montevideo">(GMT-03:00) Montevideo</option>
            <option value="America/Miquelon">(GMT-03:00) Miquelon, St. Pierre</option>
            <option value="America/Godthab">(GMT-03:00) Greenland</option>
            <option value="America/Argentina/Buenos_Aires">(GMT-03:00) Buenos Aires</option>
            <option value="America/Sao_Paulo">(GMT-03:00) Brasilia</option>
            <option value="America/Noronha">(GMT-02:00) Mid-Atlantic</option>
            <option value="Atlantic/Cape_Verde">(GMT-01:00) Cape Verde Is.</option>
            <option value="Atlantic/Azores">(GMT-01:00) Azores</option>
            <option value="Europe/Belfast">(GMT) Greenwich Mean Time : Belfast</option>
            <option value="Europe/Dublin">(GMT) Greenwich Mean Time : Dublin</option>
            <option value="Europe/Lisbon">(GMT) Greenwich Mean Time : Lisbon</option>
            <option value="Europe/London">(GMT) Greenwich Mean Time : London</option>
            <option value="Africa/Abidjan">(GMT) Monrovia, Reykjavik</option>
            <option value="Europe/Amsterdam">(GMT+01:00) Amsterdam, Berlin, Bern, Rome, Stockholm, Vienna</option>
            <option value="Europe/Belgrade">(GMT+01:00) Belgrade, Bratislava, Budapest, Ljubljana, Prague</option>
            <option value="Europe/Brussels">(GMT+01:00) Brussels, Copenhagen, Madrid, Paris</option>
            <option value="Africa/Algiers">(GMT+01:00) West Central Africa</option>
            <option value="Africa/Windhoek">(GMT+01:00) Windhoek</option>
            <option value="Asia/Beirut">(GMT+02:00) Beirut</option>
            <option value="Africa/Cairo">(GMT+02:00) Cairo</option>
            <option value="Asia/Gaza">(GMT+02:00) Gaza</option>
            <option value="Africa/Blantyre">(GMT+02:00) Harare, Pretoria</option>
            <option value="Asia/Jerusalem">(GMT+02:00) Jerusalem</option>
            <option value="Europe/Minsk">(GMT+02:00) Minsk</option>
            <option value="Asia/Damascus">(GMT+02:00) Syria</option>
            <option value="Europe/Moscow">(GMT+03:00) Moscow, St. Petersburg, Volgograd</option>
            <option value="Africa/Addis_Ababa">(GMT+03:00) Nairobi</option>
            <option value="Asia/Tehran">(GMT+03:30) Tehran</option>
            <option value="Asia/Dubai">(GMT+04:00) Abu Dhabi, Muscat</option>
            <option value="Asia/Yerevan">(GMT+04:00) Yerevan</option>
            <option value="Asia/Kabul">(GMT+04:30) Kabul</option>
            <option value="Asia/Yekaterinburg">(GMT+05:00) Ekaterinburg</option>
            <option value="Asia/Tashkent">(GMT+05:00) Tashkent</option>
            <option value="Asia/Kolkata">(GMT+05:30) Chennai, Kolkata, Mumbai, New Delhi</option>
            <option value="Asia/Katmandu">(GMT+05:45) Kathmandu</option>
            <option value="Asia/Dhaka">(GMT+06:00) Astana, Dhaka</option>
            <option value="Asia/Novosibirsk">(GMT+06:00) Novosibirsk</option>
            <option value="Asia/Rangoon">(GMT+06:30) Yangon (Rangoon)</option>
            <option value="Asia/Bangkok">(GMT+07:00) Bangkok, Hanoi, Jakarta</option>
            <option value="Asia/Krasnoyarsk">(GMT+07:00) Krasnoyarsk</option>
            <option value="Asia/Hong_Kong">(GMT+08:00) Beijing, Chongqing, Hong Kong, Urumqi</option>
            <option value="Asia/Irkutsk">(GMT+08:00) Irkutsk, Ulaan Bataar</option>
            <option value="Australia/Perth">(GMT+08:00) Perth</option>
            <option value="Australia/Eucla">(GMT+08:45) Eucla</option>
            <option value="Asia/Tokyo">(GMT+09:00) Osaka, Sapporo, Tokyo</option>
            <option value="Asia/Seoul" selected>(GMT+09:00) Seoul</option>
            <option value="Asia/Yakutsk">(GMT+09:00) Yakutsk</option>
            <option value="Australia/Adelaide">(GMT+09:30) Adelaide</option>
            <option value="Australia/Darwin">(GMT+09:30) Darwin</option>
            <option value="Australia/Brisbane">(GMT+10:00) Brisbane</option>
            <option value="Australia/Hobart">(GMT+10:00) Hobart</option>
            <option value="Asia/Vladivostok">(GMT+10:00) Vladivostok</option>
            <option value="Australia/Lord_Howe">(GMT+10:30) Lord Howe Island</option>
            <option value="Etc/GMT-11">(GMT+11:00) Solomon Is., New Caledonia</option>
            <option value="Asia/Magadan">(GMT+11:00) Magadan</option>
            <option value="Pacific/Norfolk">(GMT+11:30) Norfolk Island</option>
            <option value="Asia/Anadyr">(GMT+12:00) Anadyr, Kamchatka</option>
            <option value="Pacific/Auckland">(GMT+12:00) Auckland, Wellington</option>
            <option value="Etc/GMT-12">(GMT+12:00) Fiji, Kamchatka, Marshall Is.</option>
            <option value="Pacific/Chatham">(GMT+12:45) Chatham Islands</option>
            <option value="Pacific/Tongatapu">(GMT+13:00) Nuku'alofa</option>
            <option value="Pacific/Kiritimati">(GMT+14:00) Kiritimati</option>
        </select>
        <label for="dansangPasswordInput">글쓴이 암호</label>
        <input class="u-full-width" type="text" id="dansangPasswordInput" name="dansang-password" <?php $module->utils->defaultPostValue("dansang-password");?> required>
        <input class="button-primary" style="margin-top: 30px" type="submit" value="설치하기">
    </form>
</div>
<?php
include "frame.footer.php";
?>