//
//
//
//    
//email
$id_course = optional_param('id', 0, PARAM_INT); // Course Module ID
print "<body>";
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
                            IF(INSTR(statem.proposition, 'tag') = 0, '',
                            RIGHT(statem.proposition, CHAR_LENGTH(statem.proposition) - INSTR(statem.proposition, 'tag') - 4)) as tag,
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

print   
    "<div style='
            color: #000000; 
            line-height: 180%; 
            text-align: left; 
            word-wrap: break-word;'>
        <p style='
                font-size: 14px; 
                line-height: 180%;'>
            <span style='
                    font-size: 18px; 
                    line-height: 32.4px; 
                    color: #000000;'>
                <strong>
                    <span style='
                            line-height: 32.4px; 
                            font-family: Montserrat, sans-serif; 
                            font-size: 18px;'>
                                Caro(a) professor(a),
                    </span>
                </strong>
            </span>
        </p>
        <p style='
                font-size: 14px; 
                line-height: 180%;'><br />
            <span style='
                    font-size: 16px; 
                    line-height: 28.8px; 
                    font-family: Montserrat, sans-serif;'>
                    Segue o relatório dos alunos que necessitam de sua atenção no curso de {$name}. 
                    Estes alunos apresentaram desempenho abaixo do esperado e estão com média abaixo de 5.
                    <br>
                    Sugerimos que entre em contato com eles e indique que participem de sessões de monitoria. 
                    Para cada um deles, separamos os conteúdos da disciplina em que eles apresentaram maiores dificuldades.
                    <br>
                    Para mais informações a respeito da disciplina, acesse o painel de indicadores disponível no Moodle do seu curso.
                    <br>
                    <p><em>- Mensagem automática de Dashboard de Indicadores do iTarefa</em></p>
            </span>
        </p>
</div>";

print "<div style='
                width: 100%;
                text-align: center;
            '> ". 
                html_writer::table($table_students_course_info)
        . "</div>";


print "</body>";