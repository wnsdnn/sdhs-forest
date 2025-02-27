<?php
namespace src\Controller;

class Board
{
    function insertListPro() 
    {
        [$list_title, $list_content, $owner] = post("list_title", "list_content", "owner");
        $imgTname = isset($_FILES["list_img"]) ? $_FILES["list_img"]["tmp_name"] : [];
        $count = fetch("SELECT `sn` FROM `list_tbl` ORDER BY `sn` DESC");
        $count = $count ? $count->sn+1 : 1;
        
        // 빈 배열 제거 후 인덱스 재 정렬
        $imgTname = array_values(array_filter($imgTname));

        $resultText = "";
        for($i = 0; $i<count($imgTname); $i++) {
            $path = dirname(dirname(__DIR__))."/public/resource/img/BoardImg/".$count."_".($i+1).".jpg";
            move_uploaded_file($imgTname[$i], $path);
            $resultText .= $i === 0 ? $count."_".$i+1 : "&".$count."_".$i+1;
        }

        execute("INSERT INTO `list_tbl`(`list_title`, `list_content`, `list_img`, `owner`) VALUES(?, ?, ?, ?)", [$list_title, $list_content, $resultText, $owner]);

        move("/community", "글이 작성되었습니다.");
    }

    function listDetail($args) 
    {
        $sn = $args[1];
        $sql = "SELECT lt.sn, lt.list_title, lt.list_content, lt.list_img, lt.owner, lt.list_date,"
        ." COUNT(ht.user_id) as `heart_cnt`, ut.profile_img as `profile`"
        ." FROM `list_tbl` as `lt`LEFT OUTER JOIN `heart_tbl` as `ht` on lt.sn = ht.list_sn INNER JOIN `user_tbl` as `ut` on lt.owner = ut.user_id"
        ." WHERE lt.sn = ? GROUP BY lt.sn";

        $result = fetch($sql, [$sn]);
        $result->list_img = $result->list_img === "" ? [] : explode("&", $result->list_img);

        $comments = fetchAll("SELECT ct.sn, ct.list_sn, ct.owner, ct.deep, ct.comments, ct.comments_date, ct.parent_sn, ut.profile_img FROM `comments_tbl` as `ct` LEFT OUTER JOIN `user_tbl` as `ut` on ct.owner = ut.user_id WHERE ct.parent_sn is null AND `list_sn` = ?", [$sn]);
        $comments2 = fetchAll("SELECT ct.sn, ct.list_sn, ct.owner, ct.deep, ct.comments, ct.comments_date, ct.parent_sn, ut.profile_img FROM `comments_tbl` as `ct` LEFT OUTER JOIN `user_tbl` as `ut` on ct.owner = ut.user_id WHERE ct.parent_sn is not null AND `list_sn` = ?", [$sn]);

        $date = date("Y-m-d");
        // $date = "2022-06-01";
        $hit_data = fetch("SELECT * FROM `hits_tbl` WHERE `hit_date` = ? AND `list_sn` = ?", [$date, $sn]);
        // execute("INSERT INTO `hits_tbl` VALUES (?, ?, ?)", [13, 5, "2022-06-01"]);

        if($hit_data) {
            // 해당 날짜의 데이터 값이 이미 있을때
            execute("UPDATE `hits_tbl` set `count` = ? WHERE `hit_date` = ? AND `list_sn` = ?", [$hit_data->count+1, $date ,$sn]);
        } else {
            // 해당 날짜의 데이터 값이 없을때
            execute("INSERT INTO `hits_tbl` VALUES (?, ?, ?)", [$sn, 1, date("Y-m-d")]);
        }
    
        view("/list/listDetail", ["chk" => "community", "result" => $result, "comments" => $comments, "comments2" => $comments2]);
    }

    function deleteListPro($args) 
    {
        $list_sn = $args[1];
        execute("DELETE FROM `list_tbl` WHERE `sn` = ?", [$list_sn]);

        move("/community", "게시글이 삭제되었습니다.");
    }

    function updateList($args)
    {
        $list_sn = $args[1];
        $result = fetch("SELECT * FROM `list_tbl` WHERE `sn` = ?", [$list_sn]);
        $result->list_img = $result->list_img === "" ? [] : explode("&", $result->list_img);

        view("/list/updateList", ["chk" => "community", "result" => $result]);
    }

    function updateListPro() {
        [$sn, $list_title, $list_content] = post("sn", "list_title", "list_content");
        // 원래 있는 사진들 배열로 이름 받기
        $default_img = isset($_POST['default_img']) ? $_POST['default_img'] : [];

        $resultText = "";
        // 원래 있던 사진배열 돌리기
        for($i = 1; $i<count($default_img)+1; $i++) {
            // 해당 사진의 이름을 1부터 시작해서 바꾸기
            // 1_2 => 1_1, 1_3 => 1_2 (첫번째 사진을 지웠을 때)
            // 1_1 => 1_1, 1_2 => 1_2 (아무 사진도 지우지 않았을때, 사진 이름 변동 X)
            rename(dirname(dirname(__DIR__))."/public/resource/img/BoardImg/".$default_img[$i-1].".jpg", dirname(dirname(__DIR__))."/public/resource/img/BoardImg/".$sn."_".$i.".jpg");
            // 처음으로 들어온 값이 면 앞에 '&'를 안 붙임
            $resultText .= $i === 1 ? $sn."_".$i : "&".$sn."_".$i;
        }

        // 새로 업로드 된사진 배열 받기
        $imgTname = isset($_FILES["list_img"]) ? $_FILES["list_img"]["tmp_name"] : [];
        // 원래 있던 사진배열의 크기로 시작 값 만들기
        $default_cnt = count($default_img)+1;
        // 빈 배열 제거 후 인덱스 재 정렬
        $imgTname = array_values(array_filter($imgTname));

        for($i = $default_cnt; $i<count($imgTname)+$default_cnt; $i++) {
            // 저장할 사진 이름 선언
            $path = dirname(dirname(__DIR__))."/public/resource/img/BoardImg/".$sn."_".$i.".jpg";
            // 해당이름으로 업로드
            move_uploaded_file($imgTname[$i-$default_cnt], $path);
            // 원래 있던 사진배열의 값이 1이고 $i가 1이면 맨 처음으로 입력될 사진이니 앞에 '&' 안 붙임
            $resultText .= $i === 1 ? $sn."_".$i : "&".$sn."_".$i;
        }
        
        execute("UPDATE `list_tbl` SET `list_title`=?,`list_content`=?,`list_img`=? WHERE `sn` = ?", [$list_title, $list_content, $resultText, $sn]);

        move("/community", "게시글이 수정되었습니다.");
    }

}
