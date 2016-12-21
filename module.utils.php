<?php

/* 모듈 객체 초기화 */
if (!isset($module))
    $module = new class {};

/* 모듈 생성 */
$module->{"utils"} = new class {
    /**
     * 특정 조건이 만족되지 않으면 실행 루틴을 중단하고 메시지를 표시합니다.
     */
    public function ensure($condition, $message) {
        if (!$condition) {
            echo($message);
            exit();
        }
    }


    /**
     * 해당하는 POST 변수가 존재할 경우, value 어트리뷰트를 출력합니다.
     */
    public function defaultPostValue($value) {
        if (array_key_exists($value, $_POST)) {
            echo "value=\"" . $_POST[$value] . "\"";
        }
    }
}
?>