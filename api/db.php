<?php

class Database {

    protected $pdo;

    function __construct($dbConfig) {
        $this->pdo = $this->getConnection($dbConfig);
    }

    private function getConnection($config) {
        try {
            $pdo = new PDO('mysql:host=' . $config['host'] .
                    ';dbname=' . $config['dbName'] . '', $config['dbUser'], $config['dbPassword']);
            return $pdo;
        } catch (PDOException $e) {
            print "Connection Error!: " . $e->getMessage() . " \n";
            die();
        }
    }

    public function login($username, $password) {
        $response = array();
        $encPwd = md5($password);
        $sql = "SELECT id, firstname, lastname FROM employee "
                . "WHERE username = :username AND password = :password";

        $res = $this->pdo->prepare($sql);
        $res->bindParam(':username', $username);
        $res->bindParam(':password', $encPwd);
        $res->execute();
        $record = $res->fetch(PDO::FETCH_ASSOC);
        if (!empty($record)) {
            $token = $this->getRandom(50);
            $updateSql = "UPDATE employee SET token = :token WHERE id = :id";
            $updateRes = $this->pdo->prepare($updateSql);
            $updateRes->bindParam(':token', $token);
            $updateRes->bindParam(':id', $record['id']);
            $updateRes->execute();
            //get the last assigned project
            $projectId = $this->getLastAssignedProject($record['id']);

            //insert login time and assign the project to this employee at this period
            $insertSql = "INSERT INTO tracked_time (employee_id ,project_id, login_time)
                                  VALUES (:employeeId,:projectId,:loginTime)";
            $insertRes = $this->pdo->prepare($insertSql);
            $insertRes->bindParam(':employeeId', $record['id']);
            $insertRes->bindParam(':projectId', $projectId);
            $loginTime = date("Y-m-d H:i:s");
            $insertRes->bindParam(':loginTime', $loginTime);
            $insertRes->execute();

            $response['status'] = 'success';
            $response['token'] = $token;
            $response['data'] = array(
                'firstname' => $record['firstname'],
                'lastname' => $record['lastname'],
                'assigned_project' => $projectId,
                'login_time' => $loginTime
            );
        } else {
            $response['status'] = 'login fail';
        }
        return $response;
    }

    private function verifyToken($token) {
        $sql = "SELECT id FROM employee WHERE token = :token";
        $res = $this->pdo->prepare($sql);
        $res->bindParam(':token', $token);
        $res->execute();
        $record = $res->fetch(PDO::FETCH_ASSOC);
        if (!empty($record)) {
            return $record['id'];
        } else {
            return false;
        }
    }

    private function getRandom($length) {
        $char = array_merge(range(0, 9), range('A', 'Z'), range('a', 'z'));
        $code = '';
        for ($i = 0; $i < $length; $i++) {
            $code .= $char[mt_rand(0, count($char) - 1)];
        }
        return $code;
    }

    public function logout($token) {
        $response = array();
        $key = $this->verifyToken($token);
        if (!empty($key)) {
            $sql = "SELECT id FROM employee WHERE token = :token";
            $res = $this->pdo->prepare($sql);
            $res->bindParam(':token', $token);
            $res->execute();
            $record = $res->fetch(PDO::FETCH_ASSOC);
            $employeeId = $record['id'];
            //Clear token
            $this->clearTocken($token);
            $logoutTime = date("Y-m-d H:i:s");
            $updateSql = "UPDATE tracked_time
			  SET logout_time = :logoutTime
			  WHERE employee_id = :id and logout_time IS NULL";
            $updateRes = $this->pdo->prepare($updateSql);
            $updateRes->bindParam(':logoutTime', $logoutTime);
            $updateRes->bindParam(':id', $employee_id);
            $updateRes->execute();
            $response = array('status' => 'success',
                'message' => 'Logged out',
                'employee_id' => $employeeId,
                'logout_time' => $logoutTime);
        } else {
            $response = array('status' => 'fail', 'message' => 'Invalid Token');
        }
        return $response;
    }

    public function clearTocken($token) {
        $updateSql = "UPDATE employee SET token = null WHERE token = :token";
        $updateRes = $this->pdo->prepare($updateSql);
        $updateRes->bindParam(':token', $token);
        $updateRes->execute();
    }

    public function getLastAssignedProject($employeeId) {
        $sql = "SELECT project_id FROM employee_project
				WHERE employee_id = :employeeId
				ORDER BY id DESC LIMIT 1";
        $res = $this->pdo->prepare($sql);
        $res->bindParam(':employeeId', $employeeId);
        $res->execute();
        $proRecord = $res->fetch(PDO::FETCH_ASSOC);
        return $proRecord['project_id'];
    }

    public function printCsv($csv) {
        echo '<!DOCTYPE html>
            <html>
            <head>
               <meta charset="utf-8"/>
               <title>Multiple Upload</title>
            </head>
            <body>
              <h1>Thanks for uploading file.</h1>
            <h2>File List</h2>';

        if ($file = fopen($csv, "r")) {
            echo "File opened.<br />";
            $firstline = fgets($file, 4096);
            //Gets the number of fields, in CSV-files the names of the fields are mostly given in the first line
            $num = strlen($firstline) - strlen(str_replace(',', '', $firstline));

            //save the different fields of the firstline in an array called fields
            $fields = array();
            $fields = explode(',', $firstline, ($num + 1));
            $line = array();
            $i = 0;
            //CSV: one line is one record and the cells/fields are seperated by ,
            //so $dsatz is an two dimensional array saving the records like this:
            // $dsatz[number of record][number of cell]
            $dsatz = array();
            while ($line[$i] = fgets($file, 4096)) {
                $dsatz[$i] = array();
                $dsatz[$i] = explode(',', $line[$i], ($num + 1));
                $i++;
            }
            echo "<table>";
            echo "<tr>";
            for ($k = 0; $k != ($num + 1); $k++) {
                echo "<td>" . $fields[$k] . "</td>";
            }
            echo "</tr>";

            foreach ($dsatz as $key => $number) {
                echo "<tr>";
                foreach ($number as $k => $content) {
                    echo "<td>" . $content . "</td>";
                }
            }
            echo "</table><p><a href=''>Upload more</a>
			</body>";
        }
    }

    public function saveCsv($csv) {
        $strFileHandle = fopen($csv, "r");
        $lineOfText = fgetcsv($strFileHandle, 1024, ",", "'");
        do {
            if ($lineOfText[0]) {
                $insertSql = "INSERT INTO tracked_time (employee_id , project_id,login_time,logout_time)
                                                VALUES (:employeeId,:projectId,:loginTime,:logoutTime)";
                $insertRes = $this->pdo->prepare($insertSql);
                $insertRes->bindParam(':employeeId', $lineOfText[0]);
                $insertRes->bindParam(':projectId', $lineOfText[1]);
                $insertRes->bindParam(':loginTime', $lineOfText[2]);
                $insertRes->bindParam(':logoutTime', $lineOfText[3]);
                $insertRes->execute();
            }
        } while (($lineOfText = fgetcsv($strFileHandle, 1024, ",", "'")) !== FALSE);
    }

    public function projectHours($projectId) {
        $response = array();
        $res = $this->getTrackedTimeByProject($projectId);
        $billableHours = 0;
        $record = array();
        $i = 1;
        while ($rec = $res->fetch(PDO::FETCH_ASSOC)) {
            $hours = 0;
            // Convert to timestamp
            $logoutTime = strtotime($rec['logout_time']);
            $loginTime = strtotime($rec['login_time']);
            if ($logoutTime != null) {//check if the users logged out or not
                $hours = round(abs($logoutTime - $loginTime) / 60 / 60);
                $billableHours += $hours;
                $record[$i] = array(
                    'employee_id' => $rec['employee_id'],
                    'hours' => $hours
                );
                $i++;
            }
        }
        if (!empty($record)) {
            $response['status'] = 'success';
            $response['billable_hours'] = $billableHours; //all tracked hours for the given project
            $response['data'] = $record; //explained data
        } else {
            $response['status'] = 'fail';
        }

        return $response;
    }

    public function peakTime($day, $projectId) {
        $response = array();
        $count = 0;
        $record = array();
        $res = $this->getTrackedTimeByProject($projectId);
        $i = 1;
        while ($rec = $res->fetch(PDO::FETCH_ASSOC)) {
            $allHours = array();
            // Convert to timestamp
            $logoutTime = strtotime($rec['logout_time']);
            $loginTime = strtotime($rec['login_time']);
            //Check if the day in the range
            $check = $this->checkInRange($day, $loginTime, $logoutTime);
            if ($check) {
                //Get the count of hours
                $dateDiff = round(abs($logoutTime - $loginTime) / 60);
                $hours = round(abs($dateDiff / 60)); //hours
                $minutes = round(abs($dateDiff % 60)); //minutes
                //explain which hours
                $loginHour = date('H:i:s', $loginTime);
                $logoutHour = date('H:i:s', $logoutTime);
                if ($hours == 0) {
                    $allHours[1] = array($loginHour);
                    $allHours[2] = array($logoutHour);
                } else {
                    for ($j = 0; $j < $hours; $j++) {
                        $allHours[$j + 1] = array(
                            date('H:i:s', strtotime("+$j hours", strtotime($loginHour)))
                        );
                    }
                    $allHours[$hours + 1] = array($logoutHour);
                }
                $count += $hours;
                $record[$i] = array(
                    'employee_id' => $rec['employee_id'],
                    'hours' => $hours,
                    'minutes' => $minutes,
                    'all_hours' => $allHours,
                );
            }
            $i++;
        }
        if (!empty($record)) {
            $peakHour = $this->getPeakTime($record);
            $response['status'] = 'success';
            $response['peak_hour'] = $peakHour; //peak hour for the given day
            $response['day_hours'] = $count; //all worked hours
            $response['data'] = $record; //explained data
        } else {
            $response['status'] = 'fail-no-results';
        }

        return $response;
    }

    private function getTrackedTimeByProject($projectId) {
        $sql = "SELECT * FROM tracked_time WHERE project_id = :id";
        $res = $this->pdo->prepare($sql);
        $res->bindParam(':id', $projectId);
        $res->execute();
        return $res;
    }

    private function checkInRange($day, $loginTime, $logoutTime) {
        $loginTime = date("Y-m-d", $loginTime);
        $logoutTime = date("Y-m-d", $logoutTime);
        // Check that user date is between start & end
        return (($day >= $loginTime) && ($day <= $logoutTime));
    }

    private function getPeakTime($out) {
        $arr = array();
        $i = 0;
        foreach ($out as $key => $value) {
            foreach ($value['all_hours'] as $k => $val) {
                //put all worked hours in one array
                $arr[$i] = date('H', strtotime($val[0]));
                $i++;
            }
        }
        //get the item in an array that has the most duplicates
        $c = array_count_values($arr);
        $val = array_search(max($c), $c);
        return $val;
    }

}
