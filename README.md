# The Task Solution #5451
install

1.download composer

     https://getcomposer.org/download/
2.run the command

      $php composer.phar install

3.import the database file mediaopt.sql

4.edit your configuration in api/index.php

                $dbConfig = array(
                    'host' => 'localhost',
                    'dbName' => 'mediaopt',
                    'dbUser' => 'root',
                    'dbPassword' => ''
                );
                
5.for frontend edit the root url for the webservice in js/main.js

      var rootUrl = "http://localhost/task/api";

6.to upload csv file

      file format : employee_id , project_id , login_time , logout_time

To login username= user / password= 123
<br />
<p align="center">
  <img src="https://raw.githubusercontent.com/ThaierAlkhateeb/task/master/description_pic/login.png"/>
  </p>
  
To logout<br />
<p align="center">
  <img src="https://raw.githubusercontent.com/ThaierAlkhateeb/task/master/description_pic/logout.PNG" />
  </p>
  
The billabl hours for a project<br />

<p align="center">
  <img src="https://raw.githubusercontent.com/ThaierAlkhateeb/task/master/description_pic/billable_hours.PNG" />
</p>

The peak hour for a project in a specific day<br />
in this example : the peak hour for the project_id=1 and the day=2017-03-18 is (17:00 hour)

<p align="center">)
  <img src="https://raw.githubusercontent.com/ThaierAlkhateeb/task/master/description_pic/peak_hour.PNG"/>
</p>

The frontend page<br />
<p align="center">
  <img src="https://raw.githubusercontent.com/ThaierAlkhateeb/task/master/description_pic/frontend.PNG" width="650" height="433"/>
</p>
