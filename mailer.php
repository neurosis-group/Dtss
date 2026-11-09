<?php

        ob_start();

        if (isset($_POST['submit_form'])) {

                $name = $_POST['username'];
        $email = $_POST['email'];
        $phone = $_POST['contact'];
        

        $subject="Downloads reports Data";
        $message='<html><body>
        <table style="border:solid 1px #000">
        <tr>
        <th>Name :- </th>
        <th>'.$name.'</th>
        </tr>
        <tr>
        <td>Contact :- </td>
        <td>'.$phone.'</td>
        </tr>
        <tr>
        <td>Email :- </td>
        <td>'.$email.'</td>
        </tr>
        
        
        </table>
        </body>
        </html>';
        $headers .= "FROM: dtss.in<no-reply@notification@dtss.in>\r\n";
        $headers .= "CC: shaikhabuzaid046@gmail.com\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=ISO-8859-1\r\n";

        if(mail('notification@dtss.in',$subject,$message,$headers)){
                echo "<script>window.location='thank-you.html'</script>";
                

        }
        else{
                echo "<script>window.location='404.html'</script>";
           
        }
        

    }

?>