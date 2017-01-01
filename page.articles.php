<?php
include "module.utils.php";
include "module.db.php";

$pageTitle = "나의 오늘";
$pageAuthor = "김현준";
$pageResponse = response();

function response() {
    global $module;

    // 데이터베이스 유효성 체크
    if (!file_exists("./module.db.account.php")) {
        header("Location: ./");
        return array("type" => "error",
                     "message" => "데이터베이스 설정 파일이 존재하지 않습니다.");
    }

    $result = $module->db->in("dansang_articles")
                         ->select("content")
                         ->select("timestamp")
                         ->orderBy("`no` DESC")
                         ->goAndGetAll();

    return array("type" => "success",
                 "articles" => $result);
}

include "frame.header.php";
?>

<div class="container" style="margin-top: 10%">
<?php

const WEEK_DAYS = array("일", "월", "화", "수", "목", "금", "토");


// 암호화된 부분을 HTML 요소로 가공하는 함수
function parseContent($content) {
    $chunks = explode("*", $content);
    for ($i = 1; $i < count($chunks); $i += 2) {
        $chunk = $chunks[$i];
        $data = explode("|", $chunk);
        $placeholder = "";
        $placeholderLength = intval($data[0]);
        for ($j = 0; $j < $placeholderLength; $j++) {
            $placeholder .= "*";
        }
        $chunks[$i] = "<code value=\"".$data[1]."\">" . $placeholder . "</code>";
    }
    return implode("", $chunks);
}


for ($i = 0; $i < count($pageResponse["articles"]); $i++) {
    $article = $pageResponse["articles"][$i];
    $time = strtotime($article["timestamp"]);
    $id = "date-".date("Ymd", $time);
    echo "<div id=\"". $id ."\">\n";
    echo "<h4><a href=\"#".$id."\">" .date("Y. m. d.", $time) . " " . WEEK_DAYS[date("w", $time)] . "</a></h4>\n";
    echo "<p>" . parseContent($article["content"]) . "</p>\n";
    echo "</div>\n";
    if ($i < count($pageResponse["articles"]) - 1) {
        echo "<hr/>\n";
    }
}
?>
</div>

<?php
include "frame.footer.php";
?>