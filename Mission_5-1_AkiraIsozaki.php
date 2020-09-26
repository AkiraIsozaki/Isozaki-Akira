<?php
require 'input_check.php';
//--------------------------------取得した文字列を取得し，挿入できるように編集する．-------------------------------------------------
//----form1---------------
$name = checkInput("name");
$comment = checkInput("comment");
$pass_b = checkInput("pass");
$pass = password_hash($pass_b,PASSWORD_DEFAULT);
$editline = checkInput("editline");
//----form2---------------
$del_post = checkInput("delete");
$del_pass = checkInput("del_pass");
//----form3---------------
$edit_post = checkInput("edit");
$edit_pass = checkInput("edit_pass");

$time = date("Y/m/d　H:i:s");
//------------------------------------接続開始-------------------------------------------------------------------
try {
    $pdo = new PDO(
        'mysql:dbname=データベース名;host=localhost;charset=utf8',
        'ID',
        'パスワード',
        array(
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, 
        )
    );
    //-------------------------------存在しないときはデータベース内にテーブルを作成する----------------------
    $tbname = "mychat";
    $sql = "create table if not exists $tbname"
        ." ("
        . "id INT AUTO_INCREMENT PRIMARY KEY,"
        . "name char(32),"
        . "comment TEXT,"
        . "time TEXT,"
        . "pass TEXT"
        .");";
    $stmt = $pdo->query($sql);

    //--------------------------------パスワードが8文字以上かつ重複の無い時のみ送信．----------------------------------------------------
    if (!($name===null) && !($comment===null) && !($pass_b===null) && $editline===null) {
        $pass_size = mb_strlen($pass_b);
        if ($pass_size < 8) {
            echo '<script language=javascript>alert("パスワードは8文字以上にしてください")</script>';
        } else {
            //同じパスワードを重複して登録されないようにする．
            $overlapping = 0;
            $sql = "select pass from $tbname";
            $stmt = $pdo->query($sql);
            $results = $stmt->fetchAll();
            foreach ($results as $row) {
                if (password_verify($pass_b, $row['pass'])) {//パスワードが重複した時
                    $overlapping += 1;
                }
            }
            if ($overlapping === 0) {//一つも重複しなかったとき
                //---------------------------------新しい行を挿入-------------------------------------------------------------------
                $sql = $pdo->prepare("insert into $tbname (name,comment,time,pass) values (:name, :comment, :time, :pass)");
                $sql->bindParam(':name',$name, PDO::PARAM_STR);
                $sql->bindParam(':comment',$comment, PDO::PARAM_STR);
                $sql->bindParam(':time',$time, PDO::PARAM_STR);
                $sql->bindParam(':pass',$pass, PDO::PARAM_STR);
                $sql->execute();    //送信
            } else {
                echo '<script language=javascript>alert("そのパスワードは登録できません")</script>';
            }
        }   
    }
    
   //------------------------------------削除-------------------------------------------
    if (!($del_post===null) && !($del_pass===null)) {
        $sql = "select pass from $tbname";
        $stmt = $pdo->query($sql);
        $results = $stmt->fetchAll();
        $d_check = 0;//最後まで０の時db上にあっているパスワードはない（間違い）
        foreach ($results as $row) {
            if (password_verify($del_pass, $row['pass'])) {
                $d_check += 1;
                $sql = "delete from $tbname where id=:id";
                $stmt = $pdo->prepare($sql);
                $stmt->bindParam(':id', $del_post, PDO::PARAM_INT); //バインドして
                $stmt->execute(); //送信
            }
        }
        if ($d_check === 0) {//最後まで一致しなかったとき
            echo '<script language=javascript>alert("パスワード及び投稿番号を確認してください")</script>';
        }
        
    }

    //------------------------------------編集したい行を返す.-------------------------------------------
    
    if (!($edit_post===null) && !($edit_pass===null)) {
        //編集したい番号の投稿を入力フォームに返す
        $sql = "select * from $tbname where id=:id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':id', $edit_post, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetchAll();
        foreach ($result as $row) {
            //check_sumが最後まで0=>idに値が一度もバインドされていない．=>パスワードは間違えている．
            $e_check = 0;
            if (password_verify($edit_pass, $row['pass'])) {
                $e_check = +1;
                $return_id = $row['id'];
                $return_name = $row['name'];
                $return_comment = $row['comment'];
                $return_time = $row['time'];
                $return_pass = 1;
            }   
        }
        if ($e_check === 0) {
            echo '<script language=javascript>alert("パスワード及び投稿番号を確認してください")</script>';
        }
    }
    
    
        
    //------------------------------------編集する----------------------------------------------
    $editline = checkInput("editline");
    if (!($name===null) && !($comment===null) && !($editline===null)) {
        $sql = "update $tbname set name=:name,comment=:comment,time=:time where id=:id";
        $stmt = $pdo->prepare($sql);
        #name, comment time id　を順にバインドする.
        $stmt->bindParam(':name', $name, PDO::PARAM_STR);
        $stmt->bindParam(':comment', $comment, PDO::PARAM_STR);
        $stmt->bindParam(':time', $time, PDO::PARAM_STR);
        $stmt->bindParam(':id', $editline, PDO::PARAM_INT);
        $stmt->execute();
    }

} catch (PDOException $e) {
    header('Content-Type: text/plain; charset=UTF-8', true, 500);
    exit($e->getMessage()); 
}
?>

<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>チャットやで</title>
    <script type="text/javascript" charset="UTF-8">
        function disp(){
            if(!window.confirm('本当にいいんですね？')){
                window.alert('キャンセルされました'); 
                return false;
            }
        }
    </script>
</head>
<body>
    <form method="POST">
        <input type="text" name="name" placeholder="名前" value="<?php if(!empty($return_name)){echo h($return_name);}?>"><br>
        <input type="text" name="comment" placeholder="コメント" value="<?php if(!empty($return_comment)){echo h($return_comment);}?>">
        <input type="hidden" name="editline" value="<?php if(!empty($return_id)){echo h($return_id);}?>"><br>
            <!-- 編集用に新しい値が返された時は，パスワードを操作しないように，入力ボックスは隠す．-->
        <input type="<?php if(empty($return_pass)){ echo "password";}else{echo "hidden";}?>" name="pass" placeholder="パスワード(8文字以上）"><br>
        <input type="submit" value="送信">
    </form>

    <form method="POST">
        <input type="number" name="delete" placeholder="削除したい投稿番号"><br> 
        <input type="password" name="del_pass" placeholder="パスワード"><br>
        <!--クリックするとウィンドウで本当に消すか確かめる. -->
        <input type="submit" value="削除" onClick="disp();">
    </form>

    <form method="POST">
        <br><input type="number" name="edit" placeholder="編集したい投稿番号"><br>
        <input type="password" name="edit_pass" placeholder="パスワード">
        <input type="submit" value="編集">
    </form>
</body>
</html>

<?php
// ---------------------------------------画面表示--------------------
$sql = "select * from $tbname";
$stmt = $pdo->query($sql);
$results = $stmt->fetchAll();
echo "<hr>";
foreach ($results as $row){
    //$rowの中にはテーブルのカラム名が入る
    echo h($row['id']) . "　";
    echo h($row['name']) . "　";
    echo h($row['comment']) . "　";
    echo h($row['time']) . "<br>";
}
echo "<hr>";

?>