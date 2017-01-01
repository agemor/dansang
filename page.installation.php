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

    if (!isset($_POST["db-name"]) || !isset($_POST["db-id"]) || !isset($_POST["db-password"]) || !isset($_POST["db-table-prefix"]) || !isset($_POST["dansang-password"])) {
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

    // password 값 추가 (해시해서)
    $passwordSql = "INSERT INTO `".$tablePrefix."dansang_settings` (`key`, `value`) VALUES ('password', '".$module->crypto->hash($_POST["dansang-password"])."');";
    $passwordSqlResult = $db->query($passwordSql);

    if (!$passwordSqlResult) {
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

    file_put_contents("./module.db.account.php", $config);

    header("Location: ./");

    return array("type" => "success");
}

include "frame.header.php";
?>
<div class="container" style="margin-top: 10%">
    <h4>단상 · 斷想<br/>설치를 시작합니다.</h4>
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
        <label for="dansangPasswordInput">단상 비밀번호</label>
        <input class="u-full-width" type="text" id="dansangPasswordInput" name="dansang-password" <?php $module->utils->defaultPostValue("dansang-password");?> required>
        <input class="button" style="margin-top: 30px" type="submit" value="설치하기">
    </form>
</div>
<?php
include "frame.footer.php";
?>