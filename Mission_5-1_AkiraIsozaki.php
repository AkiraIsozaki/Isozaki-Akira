<?php
//------------------------------------------入力値用の各種関数---------------------------------------------------
function getk($key) {
    return isset($_POST[$key]) ? htmlspecialchars($_POST[$key],ENT_QUOTES) : null;
}

//入力値が半角スペース・全角スペース・タブスペースなどのみの時はnullを返す．
function disinfection($input) {
    return preg_replace( '/\A[\p{C}\p{Z}]++|[\p{C}\p{Z}]++\z/u', '', $input) === "" ? null : $input;
}

//空でないとき取得=>サニタイズして返す．
//何も入力されていない又は，スペーで何も入力されていないときはnullを返す．
function checkInput($key) {
    return disinfection(getk($key)) ;
}

//------------------------------------接続開始-------------------------------------------------------------------
try {
    $pdo = new PDO(
        'mysql:dbname=データベース名;host=localhost;charset=utf8',
        'ユーザ名',
        'パスワード',
        array(
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,// エラーが発生した時は教える.
            PDO::ATTR_EMULATE_PREPARES => false,        // 静的プレースホルダーを使う．
        )
    );
   //-------------------------------存在しないときはデータベース内にテーブルを作成する(mychat20000)----------------------
   $sql = "create table if not exists mychat20000"
   ." ("
	. "id INT AUTO_INCREMENT PRIMARY KEY,"
	. "name char(32),"
    . "comment TEXT,"
    . "time TEXT,"
    . "pass TEXT"
    .");";
    $stmt = $pdo->query($sql);

    //--------------------------------取得した文字列を取得し，挿入できるように編集する．-------------------------------------------------
    $name = checkInput("name");
    $comment = checkInput("comment");
    $time = date("Y/m/d　H:i:s");
    $pass_b = checkInput("pass");
    $pass = password_hash($pass_b,PASSWORD_DEFAULT);

    //---------------------------------新しい行を挿入する．-------------------------------------------------------------------
    $sql = $pdo->prepare("insert into mychat20000  (name,comment,time,pass) values (:name, :comment, :time, :pass)");
    $sql->bindParam(':name',$name, PDO::PARAM_STR);
    $sql->bindParam(':comment',$comment, PDO::PARAM_STR);
    $sql->bindParam(':time',$time, PDO::PARAM_STR);
    $sql->bindParam(':pass',$pass, PDO::PARAM_STR);

    //--------------------------------パスワードが8文字以上かつ重複の無い時のみ送信．----------------------------------------------------
    if(!($pass_b === null)){
        $pass_size = mb_strlen($pass_b);
        if ($pass_size < 8){
            echo '<script language=javascript>alert("パスワードは8文字以上にしてください")</script>';
        }else{
            //同じパスワードを重複して登録されないようにする．
            $sql_check = 'select * from mychat20000';
            $stmt_check = $pdo->query($sql_check);
            $results = $stmt_check->fetchAll();
            foreach ($results as $row){
                if (password_verify($pass_b, $row['pass'])) {
                    //同じパスワードが存在すると具合が悪い．
                    //overlappingに1が代入されている状態でなければOK
                    $overlapping = 1;
                }
            }
            if ($overlapping != 1) {
                $sql->execute();    //送信
            }else{
                echo '<script language=javascript>alert("そのパスワードは登録できません")</script>';
            }
        }   
    }
    
   //------------------------------------削除-------------------------------------------
    $del_post = checkInput("delete");
    $del_pass = checkInput("del_pass");
    
    if (!($del_post === null || $del_pass === null)){
        $sql = 'select * from mychat20000';
        $stmt = $pdo->query($sql);
        $results = $stmt->fetchAll();
        //check_sumが最後まで0=>idに値が一度もバインドされていない．=>パスワードは間違えている．
        $check_sum = 0;
        foreach ($results as $row){
            if (password_verify($del_pass, $row['pass'])) {
                $id = $del_post;
                $check_sum += $id;
                $sql = 'delete from mychat20000 where id=:id';
                $stmt = $pdo -> prepare($sql);
                $stmt->bindParam(':id', $id, PDO::PARAM_INT); //バインドして
                $stmt->execute(); //送信
            }
        }
        if ($check_sum === 0){
            echo '<script language=javascript>alert("パスワード及び投稿番号を確認してください")</script>';
        }
        
    }
    

    //------------------------------------編集したい行を返す.-------------------------------------------
    $edit_post = checkInput("edit");
    $edit_pass = checkInput("edit_pass");
    if (!($edit_post === null || $edit_pass === null)){
        //編集したい番号の投稿を入力フォームに返す
        $id = $edit_post;

        $sql = 'select * from mychat20000 where id=:id';
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':id',$id,PDO::PARAM_INT);
        $stmt->execute();

        $result = $stmt->fetchAll();
        foreach($result as $row){
            //check_sumが最後まで0=>idに値が一度もバインドされていない．=>パスワードは間違えている．
            $check_sum = 0;
            if (password_verify($edit_pass,$row['pass'])){
                $check_sum = +1;
                $return_id = $row['id'];
                $return_name = $row['name'];
                $return_comment = $row['comment'];
                $return_time = $row['time'];
                $return_pass = 1;
            }   
        }
        if ($check_sum === 0){
            echo '<script language=javascript>alert("パスワード及び投稿番号を確認してください")</script>';
        }
    }
    
    
        
    //------------------------------------編集する----------------------------------------------
    $editline = checkInput("editline");
    if (!($name === null || $comment === null) && !($editline === null)){
        $id = $editline; //変更する投稿番号
        $sql = 'update mychat20000 set name=:name,comment=:comment,time=:time where id=:id';
        $stmt = $pdo->prepare($sql);
        #name, comment time id　を順にバインドする.
        $stmt->bindParam(':name', $name, PDO::PARAM_STR);
        $stmt->bindParam(':comment', $comment, PDO::PARAM_STR);
        $stmt->bindParam(':time', $time, PDO::PARAM_STR);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
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
            <input type="text" name="name" placeholder="名前"
                value="<?php if(!empty($return_name)){echo $return_name;}?>"><br>
            <input type="text" name="comment" placeholder="コメント"
                value="<?php if(!empty($return_comment)){echo $return_comment;}?>">
            <input type="hidden" name="editline" 
                value="<?php if(!empty($return_id)){echo $return_id;}?>"><br>
            <!-- 編集用に新しい値が返された時は，パスワードを操作しないように，入力ボックスは隠す．-->
            <input 
                type="<?php
                    if(empty($return_pass)){
                        echo "password"; 
                    }else{
                        echo "hidden";
                    }
                ?>" name="pass" placeholder="パスワード(8文字以上）"><br>
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
$sql = 'select * from mychat20000';
$stmt = $pdo->query($sql);
$results = $stmt->fetchAll();
echo "<hr>";
foreach ($results as $row){
    //$rowの中にはテーブルのカラム名が入る
    echo $row['id']."　";
    echo  $row['name']."　";
    echo $row['comment']."　";
    echo $row['time']."<br>";
}
echo "<hr>";

?>