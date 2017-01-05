<?php
include "module.utils.php";
include "module.db.php";

$pageTitle = "단상 설정";
$pageAuthor = "김현준";
$pageResponse = response();

function response() {
}

include "frame.header.php";
?>
<div class="container" style="margin-top: 10%">

<h4><a href="#">단상 설정하기</a></h4>
<p>여기서 단상의 세부 설정을 관리할 수 있습니다.</p>

</div>


<?php
include "frame.footer.php";
?>