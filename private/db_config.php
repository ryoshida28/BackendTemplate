<?php
    
    define("DB_SERVER", "localhost");
    define("DB_USER", "onclass");
    define("DB_PASS", "onclassweb18");
    define("DB_NAME", "accounts");

    define('DEFAULT_TIMEZONE', 'US/Pacific');

    // Define table names
    define('TB_ACCOUNTS', 'accounts');
    define('TB_PROFILE_IMAGES', 'profileImgs');
    define('TB_USERS', 'users');
    define('TB_STAFF', 'staff');
    define('TB_TITLES', 'titles');
    define('TB_ADMINS', 'admins');
    define('TB_SUBJECT_CATEGORIES', 'subjectCategories');
    define('TB_SUBJECTS', 'subjects');
    define('TB_STAFF_SUBJECTS', 'staffSubjects');
    define('TB_SESSIONS', 'sessions');
    define('TB_SESSION_REVIEWS', 'sessionReviews');
    define('TB_SUBJECT_MATCHES', 'subjectMatches');
    define('TB_SESSION_ASSIGNMENTS', 'sessionAssignments');
    define('TB_SESSION_STUDENTS', 'sessionStudents');
    define('TB_LOCATIONS', 'locations');
    define('TB_STUDENTS', 'students');
    define('TB_SCHOOLS', 'schools');
    define('NUM_TABLES', 17);
?>


<?php  
    /**
     * Makes connection to database.
     */
    function db_connect() {
        try {
            $dsn = 'mysql:host=' . DB_SERVER . '; dbname=' . DB_NAME;
            $db = new PDO($dsn, DB_USER, DB_PASS);
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            return $db;
        } catch (Exception $e) {
            $error = $e->getMessage();
            die();
        }
    }

    
    // This funcition should not be used if a Persistant Connection is created.
    function db_disconnect($db, $sthArray = array()) {
        if(isset($db)) {
            unset($db);
            foreach ($sthArray as $sth) {
                unset($sth);
            }
        }
    }

    function admin_set() {
        $db = db_connect();
        $sql = "SELECT staffID FROM " . TB_ADMINS . ";";
        $sth = $db->prepare($sql);
        $result = $sth->execute();
        db_disconnect($db, array($sth));
        if ($result) {
            if ($sth->rowCount() > 0) {
                return true;
            } else {
                return false;
            }
        } else {
            throw new Exception('Unable to check if admin is set.');
        }
    }

    function is_configured() {
        $db = db_connect();
        $sql = 'SELECT table_name FROM information_schema.tables WHERE TABLE_SCHEMA = :tb_accounts;';
        $sth = $db->prepare($sql);
        $query = $sth->execute(array(':tb_accounts' => TB_ACCOUNTS));
        return ($query && $sth->rowCount() == NUM_TABLES);
    }
    
    function config_tables() {
        $db = db_connect();
        
        // TODO - consider adding indexes depending on performance
        
        // Create Accounts Table
        
        $sql = '
                CREATE TABLE IF NOT EXISTS ' . TB_ACCOUNTS . '(
                    id          INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                    firstName   VARCHAR(30) NOT NULL,
                    lastName    VARCHAR(30) NOT NULL,
                    birthdate   DATE NOT NULL,
                    email       VARCHAR(50) NOT NULL,
                    phone       VARCHAR(15),
                    password    VARCHAR(255) NOT NULL,
                    dateJoined  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    bio         TEXT NOT NULL,

                    UNIQUE KEY(email),
                    UNIQUE KEY(phone)
                ) ENGINE=INNODB DEFAULT CHARSET=utf8;
                ';
        $sth = $db->prepare($sql);
        $sth->execute();
        
        
        // Create Profile Images Table
        $sql = '
                CREATE TABLE IF NOT EXISTS ' . TB_PROFILE_IMAGES . '(
                    accountID   INT UNSIGNED NOT NULL,
                    imgPath     VARCHAR(255) NOT NULL,
                
                    FOREIGN KEY (accountID) REFERENCES ' . TB_ACCOUNTS . '(id) 
                        ON DELETE CASCADE ON UPDATE CASCADE
                ) ENGINE=INNODB DEFAULT CHARSET=utf8;
                ';
        $sth = $db->prepare($sql);
        $sth->execute();
        
        
        // Create Users Table
        $sql = '        
                CREATE TABLE IF NOT EXISTS ' . TB_USERS . '(
                    id          INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                    accountID   INT UNSIGNED NOT NULL,
                    numSessions INT NOT NULL,
                    hours       DECIMAL(7, 2) NOT NULL, 
                    staffNotes  TEXT NOT NULL,
                    
                    FOREIGN KEY (accountID) REFERENCES ' . TB_ACCOUNTS . ' (id) 
                        ON DELETE CASCADE ON UPDATE CASCADE
                ) ENGINE=INNODB DEFAULT CHARSET=utf8;
                ';
        $sth = $db->prepare($sql);
        $sth->execute();

        // Creates Addresses Table
        // Managed by admins, holds saved addresses of locations onclass tutors at.
        $sql = '
                CREATE TABLE IF NOT EXISTS ' . TB_LOCATIONS . '(
                    id          INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                    name        VARCHAR(50) NOT NULL,
                    houseNum    VARCHAR(10) NOT NULL,
                    street      VARCHAR(80) NOT NULL,
                    city        VARCHAR(50) NOT NULL,
                    state       VARCHAR(15) NOT NULL,
                    zip         VARCHAR(10) NOT NULL
                ) ENGINE=INNODB DEFAULT CHARSET=utf8;
                ';
        $sth = $db->prepare($sql);
        $sth->execute();

        // Creates Schools Table
        // Lists all schools where students can attend
        $sql = '
                CREATE TABLE IF NOT EXISTS ' . TB_SCHOOLS . '(
                    id          INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                    locationID  INT UNSIGNED NOT NULL,
                    name        VARCHAR(80) NOT NULL,
                    nickname    VARCHAR(50) NOT NULL,

                    FOREIGN KEY (locationID) REFERENCES ' . TB_LOCATIONS . '(id)
                        ON DELETE CASCADE ON UPDATE CASCADE
                )  ENGINE=INNODB DEFAULT CHARSET=utf8;
                ';
        $sth = $db->prepare($sql);
        $sth->execute();


        // Creates Students Table
        // Lists all students under a User's account
        $sql = '
                CREATE TABLE IF NOT EXISTS ' . TB_STUDENTS . '(
                    id          INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                    userID      INT UNSIGNED NOT NULL,
                    schoolID    INT UNSIGNED NOT NULL,
                    grade       TINYINT NOT NULL,
                    firstName   VARCHAR(30) NOT NULL,
                    lastName    VARCHAR(30) NOT NULL,
                    
                    FOREIGN KEY (userID) REFERENCES ' . TB_USERS . '(id)
                        ON DELETE CASCADE ON UPDATE CASCADE,
                    FOREIGN KEY (schoolID) REFERENCES ' . TB_SCHOOLS . '(id)
                        ON DELETE CASCADE ON UPDATE CASCADE
                )  ENGINE=INNODB DEFAULT CHARSET=utf8;
                ';
        $sth = $db->prepare($sql);
        $sth->execute();
        
        
        // Create Staff Table
        $sql = '
                CREATE TABLE IF NOT EXISTS ' . TB_STAFF . '(
                    id          INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                    accountID   INT UNSIGNED NOT NULL,
                    hours       DECIMAL (7, 2) NOT NULL, 
                    
                    FOREIGN KEY (accountID) REFERENCES ' . TB_ACCOUNTS . '(id) 
                        ON DELETE CASCADE ON UPDATE CASCADE
                ) ENGINE=INNODB DEFAULT CHARSET=utf8;
                ';
        $sth = $db->prepare($sql);
        $sth->execute();
        
        
        // Create Titles Table
        // Titles refer to staff member's rank/position in Onclass
        $sql = '
                CREATE TABLE IF NOT EXISTS ' . TB_TITLES. '(
                    id          INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                    staffID     INT UNSIGNED NOT NULL,
                    title       VARCHAR(50), 
                    
                    FOREIGN KEY (staffID) REFERENCES ' . TB_STAFF . '(id) 
                        ON DELETE CASCADE ON UPDATE CASCADE
                ) ENGINE=INNODB DEFAULT CHARSET=utf8;
                ';
        $sth = $db->prepare($sql);
        $sth->execute();
        
        
        // Create Admins Table
        $sql = '
                CREATE TABLE IF NOT EXISTS ' . TB_ADMINS . '(
                    id          INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                    staffID     INT UNSIGNED NOT NULL, 
                    
                    FOREIGN KEY (staffID) REFERENCES ' . TB_STAFF . '(id) 
                        ON DELETE CASCADE ON UPDATE CASCADE
                ) ENGINE=INNODB DEFAULT CHARSET=utf8;
                ';
        $sth = $db->prepare($sql);
        $sth->execute();
           
        
        // Create Subject Categories Table
        // Defines general categories for specific subjects
        $sql = '
                CREATE TABLE IF NOT EXISTS ' . TB_SUBJECT_CATEGORIES . '(
                    id          INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                    category    VARCHAR(50)
                ) ENGINE=INNODB DEFAULT CHARSET=utf8;
                ';
        $sth = $db->prepare($sql);
        $sth->execute();

        // Create Subjects Table
        // Lists all academic subjects
        $sql = '
                CREATE TABLE IF NOT EXISTS ' . TB_SUBJECTS . '(
                    id          INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                    categoryID  INT UNSIGNED NOT NULL,
                    subject     VARCHAR(50),

                    FOREIGN KEY (categoryID) REFERENCES ' . TB_SUBJECT_CATEGORIES . '(id)
                        ON DELETE CASCADE ON UPDATE CASCADE
                ) ENGINE=INNODB DEFAULT CHARSET=utf8;
                ';
        $sth = $db->prepare($sql);
        $sth->execute();
        
        
        // Create Staff Subjects Table
        // Multikey relationship connects staff members to subjects
        $sql = '
                CREATE TABLE IF NOT EXISTS ' . TB_STAFF_SUBJECTS . '(
                    staffID     INT UNSIGNED NOT NULL,
                    subjectID   INT UNSIGNED NOT NULL,
                    
                    FOREIGN KEY (staffID) REFERENCES ' . TB_STAFF . '(id) 
                        ON DELETE CASCADE ON UPDATE CASCADE,
                    FOREIGN KEY (subjectID) REFERENCES ' . TB_SUBJECTS . '(id) 
                        ON DELETE CASCADE ON UPDATE CASCADE
                ) ENGINE=INNODB DEFAULT CHARSET=utf8;
                ';
        $sth = $db->prepare($sql);
        $sth->execute();
        
        
        // Create Sessions Table
        $sql = '
                CREATE TABLE IF NOT EXISTS ' . TB_SESSIONS . '(
                    id          INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                    locationID  INT UNSIGNED NOT NULL,
                    timestamp   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    title       VARCHAR(50) NOT NULL,
                    description TEXT NOT NULL,
                    minTutors   TINYINT UNSIGNED NOT NULL,
                    maxTutors   TINYINT UNSIGNED ,
                    minTutees   TINYINT UNSIGNED NOT NULL,
                    maxTutees   TINYINT UNSIGNED,
                    timeStart   DATETIME NOT NULL,
                    timeEnd     DATETIME NOT NULL,

                    FOREIGN KEY (locationID) REFERENCES ' . TB_LOCATIONS . '(id)
                        ON DELETE CASCADE ON UPDATE CASCADE
                ) ENGINE=INNODB DEFAULT CHARSET=utf8;
                ';
        $sth = $db->prepare($sql);
        $sth->execute();
        
        
        // Create Reviews Table
        $sql = '
                CREATE TABLE IF NOT EXISTS ' . TB_SESSION_REVIEWS . '(
                    id          INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                    sessionID   INT UNSIGNED NOT NULL,
                    userRating  DECIMAL(3, 2),
                    userNotes   TEXT,
                    tutorNotes  TEXT,
                    
                    FOREIGN KEY (sessionID) REFERENCES ' . TB_SESSIONS . '(id) 
                        ON DELETE CASCADE ON UPDATE CASCADE
                ) ENGINE=INNODB DEFAULT CHARSET=utf8;
                ';
        $sth = $db->prepare($sql);
        $sth->execute();
        
        
        // Create Subject Matches Table
        // Multikey relationship connects sessions with subjects
        // Act like tags to easily identify which tutors should take which sessions
        $sql = '
                CREATE TABLE IF NOT EXISTS ' . TB_SUBJECT_MATCHES . '(
                    subjectID   INT UNSIGNED NOT NULL,
                    sessionID   INT UNSIGNED NOT NULL,
                    
                    FOREIGN KEY (subjectID) REFERENCES ' . TB_SUBJECTS . '(id) 
                        ON DELETE CASCADE ON UPDATE CASCADE,
                    FOREIGN KEY (sessionID) REFERENCES ' . TB_SESSIONS . '(id) 
                        ON DELETE CASCADE ON UPDATE CASCADE
                ) ENGINE=INNODB DEFAULT CHARSET=utf8;
                ';
        $sth = $db->prepare($sql);
        $sth->execute();
        
        
        // Create Session Assignments Table
        // Multikey relationship connects the session with a tutor, tutee, and possible administrator
        $sql = '
                CREATE TABLE IF NOT EXISTS ' . TB_SESSION_ASSIGNMENTS .'(
                    sessionID   INT UNSIGNED NOT NULL,
                    adminID     INT UNSIGNED NOT NULL,
                    userID      INT UNSIGNED NOT NULL,
                    staffID     INT UNSIGNED NOT NULL,
                    
                    FOREIGN KEY (sessionID) REFERENCES ' . TB_SESSIONS . '(id) 
                        ON DELETE CASCADE ON UPDATE CASCADE,
                    FOREIGN KEY (adminID) REFERENCES ' . TB_ADMINS . '(id) 
                        ON DELETE CASCADE ON UPDATE CASCADE,
                    FOREIGN KEY (userID) REFERENCES ' . TB_USERS . '(id) 
                        ON DELETE CASCADE ON UPDATE CASCADE,
                    FOREIGN KEY (staffID) REFERENCES ' . TB_STAFF . '(id) 
                        ON DELETE CASCADE ON UPDATE CASCADE
                ) ENGINE=INNODB DEFAULT CHARSET=utf8;
                ';
        $sth = $db->prepare($sql);
        $sth->execute();

        $sql = '
                CREATE TABLE IF NOT EXISTS ' . TB_SESSION_STUDENTS . '(
                    sessionID   INT UNSIGNED NOT NULL,
                    studentID   INT UNSIGNED NOT NULL,
                    
                    FOREIGN KEY (sessionID) REFERENCES ' . TB_SESSIONS . '(id)
                        ON DELETE CASCADE ON UPDATE CASCADE,
                    FOREIGN KEY (studentID) REFERENCES ' . TB_STUDENTS . '(id)
                        ON DELETE CASCADE ON UPDATE CASCADE
                ) ENGINE=INNODB DEFAULT CHARSET=utf8;
                ';
        $sth = $db->prepare($sql);
        $sth->execute();
        
        db_disconnect($db, array($sth));
    }

?>