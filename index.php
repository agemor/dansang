<?php

/*
 * 데이터베이스 설정 파일이 존재하는지 확인합니다.
 * 설정 파일이 존재한다면, <단상>이 설치되어 있다는 의미이므로 글 목록 페이지를 로드합니다.
 */
if (file_exists("./module.db.account.php")) {

    // 기본 페이지
    if (!isset($_GET["page"])) {
        include "./page.articles.php";
    }

    switch ($_GET["page"]) {
        case "articles":
            include "./page.articles.php";
            break;
        case "editor":
            include "./page.editor.php";
            break;
        default:
            include "./page.articles.php";
            break;
    }
}

/*
 * 설정 파일이 존재하지 않을 경우,
 * <단상>을 설치하기 위한 페이지를 로드합니다.
 */
else {
    include "./page.installation.php";
}

?>