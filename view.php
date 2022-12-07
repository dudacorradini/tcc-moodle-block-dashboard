<?php

require_once("../../config.php");

// Define in 'block_tccdashboard.php'
$id_course = optional_param('id', 0, PARAM_INT); // Course Module ID
print $OUTPUT->header();

if ($id_course != 0) {

    //Título do Dashboard
    $query_course_name = 
        "SELECT 
            fullname 
        FROM 
            {course}
        WHERE
            id = {$id_course}
        ";
    $course_name = $DB->get_record_sql($query_course_name);  
        
    foreach($course_name as $x) $name = $x;

    print "<br><h1><div style='text-align:center; font-weight:bold'>Dashboard de desempenho nas atividades do iTarefa do curso {$name}</h1></div><br>";
    
    
    //
    //
    //Big numbers
    //
    //
    
    //1:
    //Número de exercícios propostos aos alunos
    $query_num_proposed_ex = 
        "SELECT 
            count({iassign_statement}.id)
        FROM 
            {iassign_statement}, 
            {iassign}
        WHERE 
            {iassign_statement}.visible = 1 AND
            {iassign_statement}.iassignid = {iassign}.id AND 
            {iassign}.course = {$id_course}
            ";

    $num_proposed_ex = $DB->get_record_sql($query_num_proposed_ex);  
        
    foreach($num_proposed_ex as $x) $num = $x;

    //2:
    //% de respostas
    //Número de usuários do curso
    $query_num_users_on_course = 
        "SELECT 
            count(DISTINCT userid)
        FROM 
            {user_enrolments} as us,
            {enrol} as en 
        WHERE
            en.courseid = {$id_course} AND
            us.enrolid = en.id
        ";
    $num_users_on_course = $DB->get_record_sql($query_num_users_on_course);  
    foreach($num_users_on_course as $x) $num_users = $x;
    //Número de submissões
    $query_actual_num_sub = 
        "SELECT 
        count(*)
    FROM (
        SELECT 
            all_subm.userid, 
            all_subm.iassign_statementid,
            max(all_subm.timecreated) as timecreated
        FROM 
            {iassign_allsubmissions} as all_subm, 
            {iassign_statement} as statem, 
            {iassign} as ias
        WHERE
            statem.visible = 1 AND
            statem.iassignid = ias.id AND 
            ias.course = {$id_course}  AND
            statem.id = all_subm.iassign_statementid 
        GROUP BY 1, 2) as aux
        ";

    $actual_num_sub = $DB->get_record_sql($query_actual_num_sub);  
    foreach($actual_num_sub as $x) $num_sub = $x;

    $perc_submissions = round($num_sub /($num * $num_users) * 100, 0, PHP_ROUND_HALF_EVEN);
    
    //3:
    //Média da turma
    $query_avg_grade_course = 
        "SELECT 
            ROUND(AVG(grade), 2) as avg_grade
        FROM(
            SELECT
                user_grade_fixed.id_user,
                AVG(grade) as grade
            FROM(
                SELECT
                    user_ex.userid as id_user,
                    IF(ISNULL(user_grade.grade), 0, user_grade.grade)*10 as grade
                FROM(
                    SELECT 
                        statement_aux.id as iassign_statementid,
                        all_subm.userid,
                        MAX(all_subm.timecreated) AS timecreated
                    FROM (
                        SELECT
                            statem.id
                        FROM
                            {iassign_statement} as statem,
                            {iassign} as iassign    
                     WHERE
                         statem.visible = 1 AND
                         statem.iassignid = iassign.id AND
                         iassign.course = {$id_course}
                         ) as statement_aux,
                         {iassign_allsubmissions} as all_subm
                     GROUP BY 1, 2	
         ) as user_ex
         LEFT JOIN
             {iassign_allsubmissions} as user_grade
         ON
             user_ex.iassign_statementid = user_grade.iassign_statementid AND
             user_ex.userid = user_grade.userid AND
             user_ex.timecreated = user_grade.timecreated) as user_grade_fixed
        GROUP BY 1) avg_aux
            ";

    $avg_grade_course = $DB->get_record_sql($query_avg_grade_course);  
        
    foreach($avg_grade_course as $x) $course_avg_grade = $x;
    $course_avg_grade = round($course_avg_grade, 2, PHP_ROUND_HALF_EVEN);

    //4:
    //% de participação da turma no curso
    $query_num_user_participation = 
        "SELECT
            count(DISTINCT all_subm.userid) as num_users_part
        FROM
            {iassign_allsubmissions} as all_subm, 
            {iassign_statement} as statem, 
            {iassign} as iassign
        WHERE
            statem.iassignid = iassign.id AND 
            iassign.course = {$id_course}  AND
            statem.id = all_subm.iassign_statementid";
    $num_user_participation = $DB->get_record_sql($query_num_user_participation);  
    
    foreach($num_user_participation as $x) $num_user_ex = $x;

    $participation = round($num_user_ex /$num_users * 100, 0, PHP_ROUND_HALF_EVEN);

    //impressão Big numbers
    print "
        <body>
        <div style='width: 100%; 
                    display: table-row;
                    left-margin: 20px;
                    right-margin:20px;
                    '>
            <div style='width: 25%;
                        display: table-cell;
                        text-align: center; 
                        font-size: 20px; 
                        vertical-align: middle;
                        border: 2px solid black; 
                        border-radius: 5px;
                        background: #89D4FF;
                        '> 
                Número de exercícios propostos<br><b>{$num}</b>
            </div>
            <div style='width: 25%;
                        display: table-cell;
                        text-align: center; 
                        font-size: 20px; 
                        vertical-align: middle;
                        border: 2px solid black; 
                        border-radius: 5px;
                        background: #6ed4b1;
                        '> 
                Porcentagem de respostas<br><b>{$perc_submissions}%</b>
            </div>
            <div style='width: 25%;
                        display: table-cell;
                        text-align: center; 
                        font-size: 20px; 
                        vertical-align: middle;
                        border: 2px solid black; 
                        border-radius: 5px;
                        background: #6F9AF2;'> 
                Média da turma<br><b>{$course_avg_grade}</b>
            </div>
            <div style='width: 25%;
                        display: table-cell;
                        text-align: center; 
                        font-size: 20px; 
                        vertical-align: middle;
                        border: 2px solid black; 
                        border-radius: 5px;
                        background: #B792D6;'> 
                Porcentagem de participação dos alunos <br><b>{$participation}%</b>
            </div>
        </div>
    
            ";

    //Submissões e médias dos alunos por exercício
    $query_table_stat = 
        "SELECT
            student.id_ex,
            student.ex_name,
            IF(ISNULL(student.avg_grade), '-', student.avg_grade) as avg_grade,
            IF(ISNULL(student.avg_number_of_submissions), '-', student.avg_number_of_submissions) as avg_number_of_submissions,
            IF(ISNULL(all_subm.num_us), 0 ,all_subm.num_us) AS num_of_users
        FROM
            (SELECT
                student_grade.iassign_statementid as id_ex,
                student_grade.name as ex_name,
                ROUND(AVG(student_grade.grade), 2) AS avg_grade,
                ROUND(AVG(num_subm.number_of_submissions), 1) AS avg_number_of_submissions
            FROM
                (SELECT
                last_subm.iassign_statementid,
                last_subm.id_user,
                last_subm.name,
                IF(ISNULL(all_subm.grade), 0, all_subm.grade)*10 as grade 
                FROM(
                    SELECT 
                    all_us_ex.iassign_statementid,
                    all_us_ex.id_user,
                    all_us_ex.name,
                    MAX(all_subm.timecreated) as date_time
                    FROM (
                        #todos os alunos do curso
                        SELECT DISTINCT
                            statement_aux.id as iassign_statementid,
                            statement_aux.name,
                            us.userid as id_user #id do aluno
                        FROM
                            (SELECT
                                statem.id,
                                statem.name
                            FROM
                                {iassign_statement} as statem,
                                {iassign} as iassign    
                            WHERE
                                statem.visible = 1 AND
                                statem.iassignid = iassign.id AND
                                iassign.course = {$id_course}
                            ) as statement_aux
                            LEFT JOIN
                                (SELECT 
                                    userid
                                FROM 
                                    {user_enrolments} as us
                                LEFT JOIN 
                                    {enrol} as en
                                ON 
                                    us.enrolid = en.id
                                WHERE
                                    en.courseid = {$id_course}
                                ) as us
                            ON
                                1 = 1
                        ) AS all_us_ex
                    LEFT JOIN
                        {iassign_allsubmissions} as all_subm
                    ON
                        all_us_ex.iassign_statementid = all_subm.iassign_statementid AND
                        all_us_ex.id_user = all_subm.userid
                    GROUP BY 1, 2
                    ) as last_subm
                LEFT JOIN
                    {iassign_allsubmissions} as all_subm
                ON
                    last_subm.id_user = all_subm.userid AND #para cada usuário
                    last_subm.date_time = all_subm.timecreated AND #para a data correta
                    last_subm.iassign_statementid = all_subm.iassign_statementid #para o exercício correto
                ) as student_grade
            LEFT JOIN (
                    SELECT
                        userid as id_user,
                        iassign_statementid,
                        IF(ISNULL(COUNT(all_subm.id)), 0, COUNT(all_subm.id)) as number_of_submissions
                    FROM
                        {iassign_allsubmissions} as all_subm,
                        {iassign_statement} as statem,
                        {iassign} as iassign
                    WHERE
                        statem.visible = 1 AND
                        statem.iassignid = iassign.id AND
                        iassign.course = {$id_course}  AND
                        statem.id = all_subm.iassign_statementid
                GROUP BY 1, 2
                ) AS num_subm
            ON
                num_subm.id_user = student_grade.id_user AND
                num_subm.iassign_statementid = student_grade.iassign_statementid
            GROUP BY 1
            ) AS student
        LEFT JOIN
            (SELECT
                iassign_statementid,
                IF(ISNULL(COUNT(DISTINCT userid)), 0, COUNT(DISTINCT userid)) as num_us
            FROM
                {iassign_allsubmissions}
            GROUP BY 1
            ) as all_subm
        ON
            student.id_ex = all_subm.iassign_statementid
        ORDER BY 1
        ";

    $class_table_students_status = $DB-> get_records_sql($query_table_stat);
    $table_stat = new html_table();
    $table_stat->head = array('Exercício',  'Responderam', ' Não responderam', 'Média', 'Média de submissões');
    $table_stat->width = '*';
    $table_stat->align = array('left','right','right','right','right');

    $linhas = array();
    foreach ($class_table_students_status as $records) {
        $ex_name = $records->ex_name;
        $num_of_users = $records->num_of_users;
        $num_remaining_users = ($num_users - $records->num_of_users); 
        $avg_grade = $records->avg_grade;
        $avg_number_of_submissions = $records->avg_number_of_submissions;
        $linhas[] = array($ex_name, $num_of_users, $num_remaining_users, $avg_grade, $avg_number_of_submissions);
    }
    $table_stat->data = $linhas;

    print "<div style='width: 100%;
                        text-align: center; 
                        font-size: 24px;
                        '> 
                <b>Desempenho por exercício</b>
            </div>";
    print "<div style='width: 100%;
                        text-align: center;
                        '> ";
 
    print html_writer::table($table_stat);
    print "</div>";

    //Alunos com média menor que 5:
    $query_user_course_information =
        "SELECT
            user_course_info.*,
            CONCAT(user.firstname, ' ', user.lastname) as user_name
        FROM
            (SELECT
            	user_ex.userid as id_user,
                user_ex.user_number_of_submissions,
                ROUND(AVG(user_grade.grade)*10, 2) as avg_grade
            FROM(
                SELECT 
                	us_all_subm.*,
                	count_subm.user_number_of_submissions
                FROM(
                   	SELECT 
                    	statement_aux.id as iassign_statementid,
                    	all_subm.userid,
                    	MAX(all_subm.timecreated) AS timecreated
                	FROM (
                    	SELECT
                        	statem.id
                    	FROM
                        	{iassign_statement} as statem,
                        	{iassign} as iassign    
                    	WHERE
                        	statem.visible = 1 AND
                        	statem.iassignid = iassign.id AND
                        	iassign.course = {$id_course}
                        ) as statement_aux,
                            {iassign_allsubmissions} as all_subm
                		GROUP BY 1, 2
            			) as us_all_subm
                	LEFT JOIN
                        (SELECT
                            userid,
                            COUNT(DISTINCT iassign_statementid) AS user_number_of_submissions
                        FROM
                            {iassign_allsubmissions}
                        GROUP BY 1) as count_subm
                    ON
                    	count_subm.userid = us_all_subm.userid
            ) as user_ex
            LEFT JOIN
                {iassign_allsubmissions} as user_grade
            ON
                user_ex.iassign_statementid = user_grade.iassign_statementid AND
            	user_ex.userid = user_grade.userid AND
            	user_ex.timecreated = user_grade.timecreated
            GROUP BY 1, 2) as user_course_info,
                {user} as user
            WHERE
                user.id = user_course_info.id_user AND
                user_course_info.avg_grade <= 5
            ORDER BY 1
            ";
    $class_table_students_course_info = $DB-> get_records_sql($query_user_course_information);
    $table_students_course_info = new html_table();
    $table_students_course_info->head = array('Nome', 'Porcentagem de respostas', 'Média');
    $table_students_course_info->width = '*';
    $table_students_course_info->align = array('left','right','right');

    $lines_students_course_info = array();
    foreach ($class_table_students_course_info as $records) {
        $user_name = $records->user_name;
        $perc_answer = ROUND(($records->user_number_of_submissions / $num * 100), 0) . "%";
        $avg_grade = $records->avg_grade;
        $lines_students_course_info[] = array($user_name, $perc_answer, $avg_grade);
    }
    $table_students_course_info->data = $lines_students_course_info;
    
    print "<div style='width: 100%;
                        text-align: center; 
                        font-size: 24px;
                        '> 
                <b>Alunos abaixo da média</b>
            </div>";

    print "<div style='width: 100%;
                        text-align: center;
                        '> ";

    print html_writer::table($table_students_course_info);
    
    print "</div>";


    //Exercícios com média menor que 5
    $query_exercise_information =  
        "SELECT *
        FROM (
            SELECT
                subm_number.*,
                IF(ISNULL(student_grade.grade), '-', ROUND(AVG(student_grade.grade)*10, 2)) AS avg_grade,                    
                IF(ISNULL(student_grade.number_of_submissions), '-', ROUND(AVG(student_grade.number_of_submissions), 0)) AS avg_number_of_submissions
        FROM
            (SELECT
                course_ex.*,
                COUNT(DISTINCT subm.userid) as num_of_users #número de alunos que fizeram a tarefa
            FROM
                (SELECT
                    block.id as id_block_iassign, #id de cada bloco do iassign
                    block.course,
                    block.name as block_name,
                    ex.id as id_ex
                FROM
                    {iassign} as block,
                    {iassign_statement} as ex,
                    {iassign} as iassign
                WHERE
                    block.id = ex.iassignid AND
         	        ex.visible = 1 AND
                    ex.iassignid = iassign.id AND
    	            iassign.course = {$id_course}
            ) as course_ex
     	    LEFT JOIN
     		    {iassign_allsubmissions} as subm
     	    ON
     		    course_ex.id_ex = subm.iassign_statementid
            GROUP BY 1, 2, 3, 4
            ) AS subm_number
        LEFT JOIN
            (
            SELECT
                max_grade_ex.*,
                num_subm.number_of_submissions
            FROM
                (SELECT
                    last_subm.*,
                    all_subm.grade
                FROM
                    (SELECT
                        iassign_statementid,
                        userid as id_user, #id do aluno
                        MAX(all_subm.timecreated) as date_time #data de submissão, está em bigint --> ver como traduzir para data
                    FROM
                        {iassign_allsubmissions} as all_subm,
                        {iassign_statement} as statem,
                        {iassign} as iassign
                    WHERE
                        statem.visible = 1 AND
                        statem.iassignid = iassign.id AND
                        iassign.course = {$id_course}  AND
                        statem.id = all_subm.iassign_statementid
                    GROUP BY 1, 2
                    ) AS last_subm,
                    {iassign_allsubmissions} as all_subm
                WHERE
                    last_subm.id_user = all_subm.userid AND #para cada usuário
                    last_subm.date_time = all_subm.timecreated AND #para a data correta
                    last_subm.iassign_statementid = all_subm.iassign_statementid #para o exercício correto
                ) AS max_grade_ex
            LEFT JOIN
                (SELECT
                    userid,
                    iassign_statementid,
                    COUNT(all_subm.id) as number_of_submissions
                FROM
                    {iassign_allsubmissions} as all_subm,
                    {iassign_statement} as statem,
                    {iassign} as iassign
                WHERE
                    statem.visible = 1 AND
                    statem.iassignid = iassign.id AND
                    iassign.course = {$id_course}  AND
                    statem.id = all_subm.iassign_statementid
                GROUP BY 1, 2
                ) AS num_subm
            ON
                max_grade_ex.id_user = num_subm.userid AND
                max_grade_ex.iassign_statementid = num_subm.iassign_statementid
            ) AS student_grade
        ON
            student_grade.iassign_statementid = subm_number.id_ex
        GROUP BY
            1, 2, 3, 4, 5
        ORDER BY 2, 3, 4) as ex_info
        WHERE
            ex_info.avg_grade <= 5
        ";

    $class_table_exercise_information = $DB-> get_records_sql($query_exercise_information);
    $table_exercise_information = new html_table();
    $table_exercise_information->head = array('Bloco', 'id Exercício', 'Média', 'Média de submissões');
    $table_exercise_information->width = '*';
    $table_exercise_information->align = array('left','right','right','right');


    $linhas_exercise_information = array();
    foreach ($class_table_exercise_information as $records) {
        $block_name = $records->block_name;
        $id_ex = $records->id_ex;
        $avg_grade = $records->avg_grade;
        $avg_number_of_submissions = $records->avg_number_of_submissions;
        $linhas_exercise_information[] = array($block_name, $id_ex, $avg_grade, $avg_number_of_submissions);
    }
    $table_exercise_information->data = $linhas_exercise_information;
    print "<div style='width: 100%;
                        text-align: center; 
                        font-size: 24px;
                        '> 
                <b>Exercícios com média abaixo de cinco</b>
            </div>";
    
    print "<div style='width: 100%;
                        text-align: center;
                        '> ";
    print html_writer::table($table_exercise_information);
    print "</div>";


    //todos os alunos

    $query_all_user_course_information =  
        "SELECT
            user_course_info.*,
            CONCAT(user.firstname, ' ', user.lastname) as user_name
        FROM
            (SELECT
                user_ex.userid as id_user,
                user_ex.user_number_of_submissions,
                ROUND(AVG(user_grade.grade)*10, 2) as avg_grade
            FROM(
                SELECT 
                    us_all_subm.*,
                    count_subm.user_number_of_submissions
                FROM(
                    SELECT 
                        statement_aux.id as iassign_statementid,
                        all_subm.userid,
                        MAX(all_subm.timecreated) AS timecreated
                    FROM (
                        SELECT
                            statem.id
                        FROM
                            {iassign_statement} as statem,
                            {iassign} as iassign    
                        WHERE
                            statem.visible = 1 AND
                            statem.iassignid = iassign.id AND
                            iassign.course = {$id_course}) as statement_aux,
                            {iassign_allsubmissions} as all_subm
                        GROUP BY 1, 2
                        ) as us_all_subm
                    LEFT JOIN
                        (SELECT
                            userid,
                            COUNT(DISTINCT iassign_statementid) AS user_number_of_submissions
                        FROM
                            {iassign_allsubmissions}
                        GROUP BY 1) as count_subm
                    ON
                        count_subm.userid = us_all_subm.userid
            ) as user_ex
            LEFT JOIN
                {iassign_allsubmissions} as user_grade
            ON
                user_ex.iassign_statementid = user_grade.iassign_statementid AND
                user_ex.userid = user_grade.userid AND
                user_ex.timecreated = user_grade.timecreated
            GROUP BY 1, 2) as user_course_info,
                {user} as user
            WHERE
                user.id = user_course_info.id_user
            ORDER BY 1
            ";

    $class_table_all_user_course_information = $DB-> get_records_sql($query_all_user_course_information);
    $table_all_user_course_information = new html_table();
    $table_all_user_course_information->head = array('Nome', 'Porcentagem de respostas');
    $table_all_user_course_information->width = '*';
    $table_all_user_course_information->align = array('left','right');  
    $lines_all_user_course_information = array();
    foreach ($class_table_all_user_course_information as $records) {
        $user_name = $records->user_name;
        $perc_answer = ROUND(($records->user_number_of_submissions / $num * 100), 0) . "%";
        $lines_all_user_course_information[] = array($user_name, $perc_answer);
    }
    $table_all_user_course_information->data = $lines_all_user_course_information;

    print "<div style='width: 100%;
                        text-align: center; 
                        font-size: 24px;
                        '> 
                <b>Visão geral dos alunos</b>
            </div>";
    
    print "<div style='width: 100%;
                        text-align: center;
                        '> ";

    print html_writer::table($table_all_user_course_information);
    
    print "</div>";
    print "</body>";
}
else {
    print "<br>
    <h1>
    <div style='text-align:center; font-weight:bold'>
        Dashboard não disponível
    </div>
    </h1>
    ";
}
print "<div style 'vertical-align: bottom;'>";
    print $OUTPUT->footer();
    print "</div>";


?>