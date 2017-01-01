<?php
include "module.utils.php";
include "module.db.php";
include "module.crypto.php";

error_reporting(E_ERROR | E_PARSE);

$pageTitle = "나의 오늘";
$pageAuthor = "김현준";
$pageResponse = response();

function response() {

    global $module;

    // 데이터베이스 유효성 체크
    if (!file_exists("./module.db.account.php")) {
        return array("type" => "error",
                     "message" => "데이터베이스 설정 파일이 존재하지 않습니다.");
    }

    // 시간대 가져오기
    $result = $module->db->in("dansang_settings")
                         ->select("value")
                         ->where("key", "=", "timezone")
                         ->goAndGet();

    if ($result) {
       date_default_timezone_set($result["value"]);
    }

    // 입력 파라미터가 들어왔을 경우
    if (!isset($_POST["message"])) {
        return array("type" => "success");
    }

    if (!isset($_POST["password"]) || !isset($_POST["key"])) {
        return array("type" => "error",
                     "message" => "파라미터가 충분하지 않습니다.");
    }

    // 글쓴이 암호 확인
    $result = $module->db->in("dansang_settings")
                         ->select("value")
                         ->where("key", "=", "password")
                         ->goAndGet();

    if (!$result) {
        return array("type" => "error",
                     "message" => "서버에서 글쓴이 인증 작업을 수행할 수 없었습니다.");
    }

    if (strcmp($result["value"], $module->crypto->hash($_POST["password"])) != 0) {
        return array("type" => "error",
                     "message" => "글쓴이 암호가 올바르지 않습니다.");
    }

    // 마지막으로 글 올린 시간 체크
    $result = $module->db->in("dansang_articles")
               ->select("timestamp")
               ->orderBy("`no` DESC")
               ->limit("1")
               ->goAndGet();

    // 만약 오늘 이미 작성하였다면
    if ($result) {
        if (date('Ymd') == date('Ymd', strtotime($result["timestamp"]))) {
            return array("type" => "error",
                     "message" => "하루에 한 번만 단상을 작성할 수 있습니다.");
        }
    }

    $message = $_POST["message"];
    $messageLength = mb_strlen($message, "utf-8");
    // 메시지 길이 체크
    if ($messageLength > 2048) {
        return array("type" => "error",
                     "message" => "단상이 너무 깁니다. 2000자 내외로 작성해 주세요.");
    }

    $secretMode = false;
    $secretBuffer = "";

    // 메시지 암호화
    for ($i = 0; $i < $messageLength; $i++) {
        $char = mb_substr($message, $i, 1, "utf-8");
        if (strcmp($char, "*") == 0) {
            if (!$secretMode) {
                $secretMode = true;
            } else {
                $secretMode = false;
                $encrypted .= mb_strlen($secretBuffer, "utf-8") . "|" . $module->crypto->encrypt($secretBuffer, $_POST["key"]);
                $secretBuffer = "";
            }
            $encrypted .= $char;
            continue;
        }
        if ($secretMode) {
            $secretBuffer .= $char;
        } else {
            $encrypted .= $char;
        }
    }

    // 단상 업로드
    $result = $module->db->in("dansang_articles")
               ->insert("content", $encrypted)
               ->insert("timestamp", date("Y-m-d H:i:s"))
               ->go();

    if (!$result) {
         return array("type" => "error",
                     "message" => "단상을 업데이트하지 못했습니다.");
    }

    // 성공했을 경우, 단상 리스트로 돌아갑니다.
    header("Location: ./");

    return array("type" => "success"); 
}

include "frame.header.php";
?>

<div class="container" style="margin-top: 10%">

<h4><a href="#"><?php echo(date("n")."월 ".date("j")."일");?>의 단상</a></h4>
<?php
if ($pageResponse["type"] == "success") {
    echo("<p>오늘, 당신의 하루는 어땠나요?</p>");
} else {
    echo("<p style=\"color: #DC143C\">" . $pageResponse["message"] . "</p>");
}
?>
<hr>
<form method="post">
  <label for="messageInput">삶의 기록</label>
  <textarea class="u-full-width" id="messageInput" name="message" placeholder="'*'로 감싼 부분은 암호화됩니다." required><?php echo(isset($_POST["message"]) ? $_POST["message"] : "");?></textarea>
  <div class="row">
    <div class="six columns">
      <label for="passwordInput">글쓴이 암호</label>
      <input class="u-full-width" type="password" id="passwordInput" name="password" required>
    </div>
    <div class="six columns">
      <label for="keyInput">단상 암호</label>
      <input class="u-full-width" type="text" id="keyInput" name="key" required>
    </div>
  </div>
  <input class="button-primary"  style="margin-top: 30px" type="submit" value="저장하기">
</form>
</div>

<?php
include "frame.footer.php";
?>