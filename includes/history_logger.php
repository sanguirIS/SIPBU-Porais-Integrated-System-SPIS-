<?php

if(
    session_status()
    ===
    PHP_SESSION_NONE
){

    session_start();

}


if(
    !function_exists(
        'log_activity'
    )
){

    function log_activity(
        $conn,
        $module,
        $action,
        $message,
        $recordID = null
    ){

        /* ==============================================
           ALLOWED HISTORY MODULES
        ============================================== */

        $allowedModules = [

            'Inventory',

            'Sales',

            'Members',

            'Lending',

            'Rental',

            'Archive',

            'Account'

        ];


        $module =
        trim(
            (string)$module
        );


        $action =
        trim(
            (string)$action
        );


        $message =
        trim(
            (string)$message
        );


        if(
            !in_array(
                $module,
                $allowedModules,
                true
            )
            ||
            $action === ''
            ||
            $message === ''
        ){

            return false;

        }


        /* ==============================================
           ACTOR SNAPSHOT
        ============================================== */

        $userID =
        isset(
            $_SESSION['user_id']
        )
        ?
        (int)$_SESSION['user_id']
        :
        0;


        $userName =
        trim(
            $_SESSION['name']
            ??
            'Unknown User'
        );


        $userRole =
        trim(
            $_SESSION['role']
            ??
            'Unknown Role'
        );


        $recordID =
        $recordID === null
        ?
        null
        :
        trim(
            (string)$recordID
        );


        /* ==============================================
           SAVE HISTORY LOG

           Logging failure must not stop the original
           module transaction.
        ============================================== */

        try{

            $historyTableQuery =
            mysqli_query(
                $conn,
                "SHOW TABLES LIKE 'history_logs'"
            );


            if(
                !$historyTableQuery
                ||
                mysqli_num_rows(
                    $historyTableQuery
                ) === 0
            ){

                return false;

            }


            $historyStmt =
            mysqli_prepare(
                $conn,
                "
                INSERT INTO history_logs (
                    user_id,
                    user_name,
                    user_role,
                    module,
                    action,
                    message,
                    record_id,
                    created_at
                )
                VALUES (
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    NOW()
                )
                "
            );


        if(!$historyStmt){


            return false;

        }


        mysqli_stmt_bind_param(
            $historyStmt,
            'issssss',
            $userID,
            $userName,
            $userRole,
            $module,
            $action,
            $message,
            $recordID
        );


        $logged =
        mysqli_stmt_execute(
            $historyStmt
        );


        mysqli_stmt_close(
            $historyStmt
        );



        return $logged;

        }catch(Throwable $historyError){

            error_log(
                'SPIS history_logger.php: ' .
                $historyError->getMessage()
            );

            return false;

        }

    }


}

?>