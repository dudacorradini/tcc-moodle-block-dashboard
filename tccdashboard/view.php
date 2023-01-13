<?php

require_once("../../config.php");


// Define in 'block_tccdashboard.php'
$id_course = optional_param('id', 0, PARAM_INT); // Course Module ID
print $OUTPUT->header();

if ($id_course != 0) {

    //Título do Painel de indicadores
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

    print "<br><h1><div style='text-align:center; font-weight:bold'>Painel de indicadores de desempenho nas atividades do iTarefa do curso de {$name}</h1></div><br>";
    
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

    

    //Submissões e médias dos alunos por exercício
    $query_table_stat = 
        "SELECT
            student.id_ex,
            student.ex_name,
            IF(ISNULL(student.avg_grade) OR ISNULL(all_subm.num_us), '-', ROUND(student.avg_grade / all_subm.num_us, 2)) as avg_grade,
            IF(ISNULL(student.avg_number_of_submissions) OR ISNULL(all_subm.num_us), '-', ROUND(student.avg_number_of_submissions / all_subm.num_us, 2)) as avg_number_of_submissions,
            IF(ISNULL(all_subm.num_us), 0 ,all_subm.num_us) AS num_of_users
        FROM
            (SELECT
                student_grade.iassign_statementid as id_ex,
                student_grade.name as ex_name,
                ROUND(SUM(student_grade.grade), 2) AS avg_grade,
                ROUND(SUM(num_subm.number_of_submissions), 1) AS avg_number_of_submissions
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
    $table_stat->head = array('Exercício',  'Responderam', ' Não responderam', 'Média*', 'Média de submissões*');
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
    print "
    <body>
    <nav>
        <div class='nav nav-tabs' 
            id='nav-tab' 
            role='tablist' 
            style='font-size: 20px;'
        >
            <a class='nav-link active' id='nav-home-tab' data-toggle='tab' href='#nav-home' role='tab' aria-controls='nav-home' aria-selected='true'>Geral</a>
            <a class='nav-link' id='nav-profile-tab' data-toggle='tab' href='#nav-profile' role='tab' aria-controls='nav-profile' aria-selected='false'>Alunos</a>
            <a class='nav-link' id='nav-contact-tab' data-toggle='tab' href='#nav-contact' role='tab' aria-controls='nav-contact' aria-selected='false'>Exercícios</a>
        </div>
    </nav>
        <div class='tab-content' id='nav-tabContent'>
        ";
    
    
    print " <div class='tab-pane fade show active' id='nav-home' role='tabpanel' aria-labelledby='nav-home-tab'>
                <br>
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
                <br>
                <p>
                <div style='width: 100%;
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
    print "<div>*Consideram somente alunos que responderam aos exercícios</div></p> ";
    $ex_name_array = [];
    $ex_name_array = array($ex_name);
    
    /*$chart = new core\chart_bar();
    $series = new core\chart_series('Média', $avg_grade);
    $chart->add_series($series);
    $chart->set_labels($ex_name_array);
    echo $OUTPUT->render($chart);
    //print $ex_name_array;
    //print $series;*/
    $series_arr_avg_grade = array();
    $series_arr_avg_subm = array();
    foreach ($class_table_students_status as $records) {
        $avg_grade = $records->avg_grade;
        $ex_name = $records->ex_name;
        $avg_number_of_submissions = $records->avg_number_of_submissions;
        $series_arr_avg_grade[] = $avg_grade;
        $series_arr_avg_subm[] = $avg_number_of_submissions;
        $labels[] = $ex_name;
    }
    //gráfico de nota e submissões
    print "<div style='width: 80%;
                margin-left: auto;
                margin-right: auto;'>";
        $chart = new \core\chart_bar();
        $subm = new \core\chart_series('Submissões', $series_arr_avg_subm);
        $grade = new \core\chart_series('Nota', $series_arr_avg_grade);
        $chart->set_title('Média de nota e de submissões por exercício');
        $chart->add_series($grade);
        $chart->add_series($subm);
        $chart->set_labels($labels);
        $chart->set_legend_options(['display' => false]);
        $yaxis1 = $chart->get_yaxis(0, true);
        $yaxis2 = $chart->get_yaxis(1, true);
        $yaxis2->set_position('right');
        $CFG->chart_colorset = ['#B792D6', '#6F9AF2'];
        echo $OUTPUT->render($chart);
    print " </div>";



    print "<div style='width: 100%; 
                        display: table-row;
                        vertical-align: middle;
                '>
                    <div style='width:520px;
                                height:250px;
                                display: table-cell;
                                text-align: center; 
                                vertical-align: middle;
                                '> ";
                        //gráfico de Média de nota
                        $grade = new \core\chart_series('Média', $series_arr_avg_grade);
                        $chart = new \core\chart_bar();
                        $chart->set_title('Média de nota por exercício');
                        $chart->add_series($grade);
                        $chart->set_labels($labels);
                        $chart->set_legend_options(['display' => false]);
                        $CFG->chart_colorset = ['#6F9AF2'];
                        echo $OUTPUT->render($chart);


                   print" </b></div>
                    <div style='width:520px;
                                height:250px;
                                display: table-cell;
                                text-align: center;
                                vertical-align: middle;
                                '> ";
                        //gráfico de Média de submissões
                        $subm = new \core\chart_series('Média', $series_arr_avg_subm);
                        $chart = new \core\chart_bar();
                        $chart->set_title('Média de submissões por exercício');
                        $chart->add_series($subm);
                        $chart->set_labels($labels);
                        $chart->set_legend_options(['display' => false]);
                        $CFG->chart_colorset = ['#B792D6'];
                        
                        echo $OUTPUT->render($chart);
                print"</b>
                    </div>
                    </div> ";
    print "</div>";
    
    //Alunos com média menor que 5:
    $query_user_course_information =
        "SELECT 
            user_grade.*,
            aux_text.text_dificulty
        FROM
            (SELECT
                user_course_info.*,
                CONCAT(user.firstname, ' ', user.lastname) as user_name
            FROM
                (SELECT
                    user_ex.userid as id_user,
                    user_ex.user_number_of_submissions,
                    ROUND(AVG(user_grade.grade)*10, 2) as avg_grade
                FROM
                    (SELECT
                        us_all_subm.iassign_statementid,
                        us_all_subm.userid,
                        us_all_subm.timecreated,
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
                    user_course_info.avg_grade < 5
                ORDER BY 1) as user_grade
        LEFT JOIN
            (SELECT
                userid,
                CONCAT_WS(', ', ex_array_matrix, 
                                ex_test, 
                                ex_function, 
                                ex_variables, 
                                ex_loop, 
                                ex_conditional, 
                                ex_in_output, 
                                ex_pointer_references, 
                                ex_class,  
                                ex_math_function) as text_dificulty
            FROM
                (SELECT
                    userid,
                    IF(INSTR(tag, 'a') > 0, 'Vetor e matriz', null) as ex_array_matrix,
                    IF(INSTR(tag, 'b') > 0, 'Teste', null) as ex_test,
                    IF(INSTR(tag, 'c') > 0, 'Função', null) as ex_function,
                    IF(INSTR(tag, 'd') > 0, 'Conversão de variável', null) as ex_variables,
                    IF(INSTR(tag, 'e') > 0, 'Laços de repetição', null) as ex_loop,
                    IF(INSTR(tag, 'f') > 0, 'Laço condicional', null) as ex_conditional,
                    IF(INSTR(tag, 'g') > 0, 'Entrada/saída', null) as ex_in_output,
                    IF(INSTR(tag, 'h') > 0, 'Ponteiro e referência', null) as ex_pointer_references,
                    IF(INSTR(tag, 'i') > 0, 'Classe', null) as ex_class,
                    IF(INSTR(tag, 'j') > 0, 'Funções matemáticas', null) as ex_math_function
                FROM
                    (SELECT
                        userid,
                        GROUP_CONCAT(tag) as tag
                    FROM
                        (SELECT
                            tag_aux.*,
                            ROUND(AVG(user_grade.grade)*10, 2) as avg_grade
                        FROM
                            (SELECT
                                statem.id,
                                statem.name,
                                statem.iassignid,
                                IF(INSTR(statem.proposition, 'Id:') = 0, '',
                                RIGHT(statem.proposition, CHAR_LENGTH(statem.proposition) - INSTR(statem.proposition, 'Id:') - 4)) as tag,
                                all_subm.userid,
                                MAX(all_subm.timecreated) AS timecreated
                            FROM 
                                {iassign_statement} as statem,
                                {iassign_allsubmissions} as all_subm,
                                {iassign} as iassign
                            WHERE
                                statem.visible = 1 AND
                                statem.iassignid = iassign.id AND
                                iassign.course = {$id_course}
                            GROUP BY 1,2,3,4,5
                            ) as tag_aux
                        LEFT JOIN
                            {iassign_allsubmissions} as user_grade
                        ON
                            tag_aux.id = user_grade.iassign_statementid AND
                            tag_aux.userid = user_grade.userid AND
                            tag_aux.timecreated = user_grade.timecreated
                        GROUP BY 1, 2, 3, 4, 5, 6
                    ) as user_course_info
            WHERE
                avg_grade < 5
            GROUP BY 1) as tag_concat_aux) as tag_aux) as aux_text
        ON
            aux_text.userid = user_grade.id_user
    ";

    $class_table_students_course_info = $DB-> get_records_sql($query_user_course_information);
    $table_students_course_info = new html_table();
    $table_students_course_info->head = array('Nome', 'Porcentagem de respostas', 'Média', 'Principais dificuldades');
    $table_students_course_info->width = '*';
    $table_students_course_info->align = array('left','right','right', 'left');

    $lines_students_course_info = array();
    foreach ($class_table_students_course_info as $records) {
        $user_name = $records->user_name;
        $perc_answer = ROUND(($records->user_number_of_submissions / $num * 100), 0) . "%";
        $avg_grade = $records->avg_grade;
        $text_dificulty = $records->text_dificulty;
        $lines_students_course_info[] = array($user_name, $perc_answer, $avg_grade, $text_dificulty);
    }
    $table_students_course_info->data = $lines_students_course_info;

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


    print "<div class='tab-pane fade' 
                id='nav-profile' 
                role='tabpanel' 
                aria-labelledby='nav-profile-tab'>";
    print "<br>
                <p>
                <div style='width: 100%;
                    text-align: center; 
                    font-size: 24px;
                    '> 
                    <b>Alunos abaixo da média</b>
                </div>";

    print "<div style='width: 100%;
                text-align: center;
                '> ";

    print html_writer::table($table_students_course_info);

    print "</div></p>";

    print "<br><p><div style='width: 100%;
                        text-align: center; 
                        font-size: 24px;
                        '> 
                <b>Visão geral dos alunos</b>
            </div>";
    
    print "<div style='width: 100%;
                        text-align: center;
                        '> ";

    print html_writer::table($table_all_user_course_information);
    
    print "</div>
            </p>
            </div>";

    //Exercícios com média menor que 5
    $query_exercise_information =  
           "SELECT *
           FROM (
               SELECT
                   subm_number.id_ex,
                   subm_number.ex_name,
                   subm_number.id_block_iassign,
                   subm_number.course,
                   subm_number.block_name,
                   subm_number.num_of_users,
                   CASE
                    WHEN subm_number.ex_dificulty = 1 THEN 'Fácil'
                    WHEN subm_number.ex_dificulty = 2 THEN 'Média'
                    WHEN subm_number.ex_dificulty = 3 THEN 'Difícil'
                    ELSE '' END as ex_dificulty,
                   CONCAT_WS(', ', subm_number.ex_array_matrix, 
                               subm_number.ex_test, 
                               subm_number.ex_function, 
                               subm_number.ex_variables, 
                               subm_number.ex_loop, 
                               subm_number.ex_conditional, 
                               subm_number.ex_in_output, 
                               subm_number.ex_pointer_references, 
                               subm_number.ex_class,  
                               subm_number.ex_math_function) as text_dificulty,
                    IF(ISNULL(student_grade.grade), '-', ROUND(AVG(student_grade.grade)*10, 2)) AS avg_grade,
                    IF(ISNULL(student_grade.number_of_submissions), '-', ROUND(AVG(student_grade.number_of_submissions), 0)) AS avg_number_of_submissions
                FROM
                    (SELECT
                        course_ex.id_ex,
                        course_ex.ex_name,
                        course_ex.id_block_iassign,
                        course_ex.course,
                        course_ex.block_name,
                        LEFT(tag, 1) as ex_dificulty,
                        IF(INSTR(course_ex.tag, 'a') > 0, 'Vetor e matriz', null) as ex_array_matrix,
                        IF(INSTR(course_ex.tag, 'b') > 0, 'Teste', null) as ex_test,
                        IF(INSTR(course_ex.tag, 'c') > 0, 'Função', null) as ex_function,
                        IF(INSTR(course_ex.tag, 'd') > 0, 'Conversão de variável', null) as ex_variables,
                        IF(INSTR(course_ex.tag, 'e') > 0, 'Laços de repetição', null) as ex_loop,
                        IF(INSTR(course_ex.tag, 'f') > 0, 'Laço condicional', null) as ex_conditional,
                        IF(INSTR(course_ex.tag, 'g') > 0, 'Entrada/saída', null) as ex_in_output,
                        IF(INSTR(course_ex.tag, 'h') > 0, 'Ponteiro e referência', null) as ex_pointer_references,
                        IF(INSTR(course_ex.tag, 'i') > 0, 'Classe', null) as ex_class,
                        IF(INSTR(course_ex.tag, 'j') > 0, 'Funções matemáticas', null) as ex_math_function,
                        COUNT(DISTINCT subm.userid) as num_of_users #número de alunos que fizeram a tarefa
                    FROM
                        (SELECT
                            ex.id as id_ex,
                            ex.name as ex_name,
                            block.id as id_block_iassign, #id de cada bloco do iassign
                            block.course,
                            block.name as block_name,
                            IF(INSTR(ex.proposition, 'Id:') = 0, null, 
                            RIGHT(ex.proposition, CHAR_LENGTH(ex.proposition) - INSTR(ex.proposition, 'Id:') - 3)) as tag
                        FROM
                            {iassign} as block,
                            {iassign_statement} as ex
                        WHERE
                            block.id = ex.iassignid AND
                            ex.visible = 1 AND
                            block.course = {$id_course}
                        ) as course_ex
                    LEFT JOIN
                        {iassign_allsubmissions} as subm
                    ON
                        course_ex.id_ex = subm.iassign_statementid
                    GROUP BY 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16
                    ) AS subm_number
                LEFT JOIN
                    (SELECT
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
                    1, 2, 3, 4, 5, 6, 7, 8
                ORDER BY 3, 1) as ex_info
            WHERE
                ex_info.avg_grade < 5
       ";
    
    $class_table_exercise_information = $DB-> get_records_sql($query_exercise_information);
    $table_exercise_information = new html_table();
    $table_exercise_information->head = array('Bloco', 'Exercício', 'Dificuldade', 'Conteúdo', 'Média', 'Média de submissões');
    $table_exercise_information->width = '*';
    $table_exercise_information->align = array('left','left','left', 'left', 'right','right');
    
    
    $linhas_exercise_information = array();
    foreach ($class_table_exercise_information as $records) {
        $block_name = $records->block_name;
        $ex_name = $records->ex_name;
        $ex_dificulty = $records->ex_dificulty;
        $text_dificulty = $records->text_dificulty;
        $avg_grade = $records->avg_grade;
        $avg_number_of_submissions = $records->avg_number_of_submissions;
        $linhas_exercise_information[] = array($block_name, $ex_name, $ex_dificulty, $text_dificulty, $avg_grade, $avg_number_of_submissions);
    }
    $table_exercise_information->data = $linhas_exercise_information;

    print "  
        <div class='tab-pane fade' id='nav-contact' role='tabpanel' aria-labelledby='nav-contact-tab'>";

    print "<br>
            <p>
            <div style='width: 100%;
                        text-align: center; 
                        font-size: 24px;
            '> 
                <b>Exercícios com média abaixo de cinco</b>
            </div>";

        print "<div style='width: 100%;
                    text-align: center;
                    '> 
            
            ";
        print html_writer::table($table_exercise_information);
        print "</div></p></div>";

    print "</body>";
}
else {
    print "<br>
    <h1>
    <div style='text-align:center; font-weight:bold'>
        Painel de Indicadores não disponível
    </div>
    </h1>
    ";
}
print "<div style 'vertical-align: bottom;'>";
    print $OUTPUT->footer();
    print "</div>";


?>