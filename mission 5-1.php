<!DOCTYPE html>
<html lang="ja">
    <head>
        <meta charset="utf-8">
        <title>mission 5-1</title>
    </head>
    <body>
        
        <?php
            //入力フォームに名前とコメントを表示させる処理を行うために、名前とコメントを格納する変数を用意する
            $in_name="名前";
            $in_comment="コメント";
            //新規登録するか編集するか確認するためのフラグ(-1なら新規投稿)異なるなら編集
            if(empty($_POST["edit_flag"])/*(isset($_POST["edit_flag"]))&&($_POST["edit_flag"]===NULL)*/)//最初に新規投稿か編集か確認するためのフォームが存在しないなら
            {
                $edit_flag=-1;//編集フラグを-1にして新規投稿もーどにする
            }
            else
            {
                //新規投稿か編集か確認するフォームから受け取る
                $edit_flag=$_POST["edit_flag"];
            }


        //データベース構築
         $dsn = 'データベース名';
         $user = 'ユーザー名';
         $password = 'パスワード';
         $pdo = new PDO($dsn, $user, $password, array(PDO::ATTR_ERRMODE => PDO::ERRMODE_WARNING));

         //テーブルを作成
         create_tabale($pdo);
        

        //削除フォームに削除番号とパスが入力されているなら
        if((empty($_POST["delete"])||empty($_POST["password3"]))==false)
        {               
            //削除番号を取得
            $delete_number=$_POST["delete"];
            //削除番号のコメントを取得
            $delete_data=get_data($pdo,$delete_number);
            foreach($delete_data as $row)
            {
                //削除番号のパスワードを取得
                $password=$row["password"];
            }
            //パスワードが合ってるか確認する
            if($_POST["password3"]==$password)
            {
                //削除処理
                delete_data($pdo,$delete_number);
            }
            else{
                echo "パスワードが異なります。"."<br>";
            }
        }

        //編集入力フォームに編集番号とパスが入力されているなら
        else if((empty($_POST["change"])||empty($_POST["password2"]))==false)
        {               
            //編集番号を取得
            $edit_number=$_POST["change"];

            //編集番号のコメントを取得
            $edit_data=get_data($pdo,$edit_number);
            foreach($edit_data as $row)
            {
                //編集番号のコメントデータを取得
                $name=$row["name"];
                $comment=$row["comment"];
                $password=$row["password"];
            }

            //パスワードが合ってるか比較し合っているなら以下の処理を行う
            if($_POST["password2"]==$password)
            {
                //入力フォームに編集対象の名前とコメントをそれぞれ入れる。
                $in_name=$name;
                $in_comment=$comment;

                //編集処理フラグに編集対象番号を入れる
                $edit_flag=$edit_number;

            }
            else
            {
                echo "パスワードが異なります。"."<br>";
            }
        }       
                   
        //入力フォームに入力されているならコメント登録処理を行う
        else if((empty($_POST["name"])||empty($_POST["comment"])||empty($_POST["password1"]))==false)
        {
            //var_dump($edit_flag);
            //編集フラグの値が-1ではないなら編集対象のコメントに対して編集処理を行う
            if($edit_flag!=-1)
            {
                edit_data($pdo,$edit_flag,$_POST["name"],$_POST["comment"]);
                //編集フラグを折る
                $edit_flag=-1;
            }
            else
            {
                //データベースにコメントを登録する処理を行う
                add_data($pdo,$_POST["name"],$_POST["comment"],$_POST["password1"]);
            }
        }

        //コメント全消去フォームから送られてきたなら
        else if(empty($_POST["password4"])==false)
        {   
            //送られてきたパスワードが管理者パスワードと一致するか確認する
            if($_POST["password4"]=="tpULV")
            {
                //一致するならコメント全消去
                delete_data($pdo); //削除指定番号を指定しない場合-1が渡され、コメント全削除になる
                echo "<br>コメントを全削除しました";
            }
            //パスワードが一致しないなら注意文を出す
            else
            {
                echo "<br>パスワードが異なります";
            }
        }


        

        //何も入力されてないなら注意文を出す
        else
        {
            echo "<br>"."名前とコメントとパスワードを入力してください";
        }
        //var_dump($in_comment);
        ?>

        <!--コメント新規登録フォームはadd_comment.phpに送信する!-->
        <form action="" method="post">
            <!--名前入力フォーム!-->
            <input type="text" name="name" placeholder="名前" value=<?php echo $in_name; ?>><br>
            <!--コメント入力フォーム!-->
            <input type="text" name="comment" placeholder="コメント" value=<?php echo $in_comment; ?>><br>
            <input type="password" name="password1" placeholder="password">
            <!--編集処理用フォーム!-->
            <!--編集フラグが立っていないなら、新規登録モード、立っているなら編集モードにする!-->
            <input type="hidden" name="edit_flag" value=<?php echo $edit_flag?>><br>
             <!--送信ボタン!-->
            <input type="submit" value="送信"><br>

            <!--min=0と書くことでマイナスの投稿番号が入力されるのを防ぐ!-->
            <br>コメント編集<br>
            <input type="number" name="change" placeholder="0" min=0>
            <input type="password" name="password2" placeholder="password">
            <input type="submit" value="編集"><br>
            
            <br>コメント削除<br>
            <!--min=0と書くことでマイナスの投稿番号が入力されるのを防ぐ!-->
            削除番号:<input type="number" name="delete" placeholder="0" min=0>
            <input type="password" name="password3" placeholder="password">
            <input type="submit" value="削除"><br>
        </form>
    </body>
    <?php

        //SQLにデータを追加
        function add_data($pdo,$name,$comment,$password)
        {
            $date=date("Y-m-d H:i:s");

            //コメントを書き込む問い合わせ文
            $sql=$pdo->prepare("INSERT INTO thread (name,comment,password,date) VALUES (:name,:comment,:password,:date)");
            $sql->bindParam(':name',$name,PDO::PARAM_STR);
            $sql->bindParam(':comment',$comment,PDO::PARAM_STR);
            $sql->bindParam(':password',$password,PDO::PARAM_STR);
            $sql->bindvalue(':date',$date,PDO::PARAM_STR);
            $sql->execute();
        }
        
        //対象の番号のコメントデータを取得する
        function get_data($pdo,$number)
        {
            //対象の番号のコメントデータを取得する問い合わせ文
            $sql='SELECT * FROM thread WHERE id=:id';
            $stmt=$pdo->prepare($sql);
            //idに対象番号を代入する
            $stmt->bindParam(':id',$number,PDO::PARAM_INT);
            $stmt->execute();
            $results=$stmt->fetchAll();
            
            //取得結果を返す
            return $results;
        }

        //データ編集
        function edit_data($pdo,$edit_number,$name,$comment)
        {
            //コメントのデータ編集をする問い合わせ文
            $sql='UPDATE thread SET name=:name,comment=:comment WHERE id=:id';
            $stmt=$pdo->prepare($sql);
            $stmt->bindParam(':name',$name,PDO::PARAM_STR);//nameを$name
            $stmt->bindParam(':comment',$comment,PDO::PARAM_STR);//commentを$commnet
            $stmt->bindParam(':id',$edit_number,PDO::PARAM_INT);//idを$edit_numberとする
            $stmt->execute();     
            
        }
        
        //データ削除(numberは削除対象のコメント番号が入る、-1が入るとコメント全消去になる)
        function delete_data($pdo,$number=-1)
        {
            //var_dump($number);

            //$numberが-1ならテーブル自体を削除する
            if($number==-1)
            {
                //テーブルを削除する問い合わせ文
                $sql = 'DROP TABLE thread';
                $stmt = $pdo->query($sql);

                //テーブルが削除されたので、再度テーブルを作成する
                create_tabale($pdo);
            }

            //コメントを削除する問い合わせ文
            $sql='delete from thread where  id=:id';
            $stmt=$pdo->prepare($sql);
            $stmt->bindParam(':id',$number,PDO::PARAM_INT);
            $stmt->execute();
        }

        //SQLにデータを表示
        function show_data($pdo)
        {
            $sql='SELECT *FROM thread';
            $stmt=$pdo->query($sql);
            $results=$stmt->fetchAll();
            foreach($results as $row)
            {
                echo "<hr>";
                echo $row['id'].' ';
                echo $row['name'].' ';
                echo $row['comment'].' ';
                echo $row['date'].' ';
                echo "<br>";                
            }
            echo "<hr>";
        }
        
        //テーブルを作成処理
        function create_tabale($pdo)
        {
            //テーブルが存在しなければテーブルを作成
            $sql="CREATE TABLE IF NOT EXISTS thread"
            ."("
            ."id INT AUTO_INCREMENT PRIMARY KEY,"
            ."name char(32),"
            ."comment TEXT,"
            ."date datetime,"
            ."password TEXT"
            .")";
            //テーブルを作成
            $stmt=$pdo->query($sql);
        }

         
        //データを表示する
        show_data($pdo);
    ?>


    
    コメント全消去(管理人専用)
    <form action="" method="post">
            <!--コメント全消去フォーム(管理人専用)!-->
            <!--全消去専用パスワード入力テキスト!-->
            <input type="password" name="password4" placeholder="password"><br>
             <!--送信ボタン!-->
            <input type="submit" value="送信"><br>

           
    </form>
    

</html>