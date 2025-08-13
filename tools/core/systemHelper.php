<?php

class SystemHelper
{
    function updateResFileOne($rowNum, $numpp, $fileName, $dl_date, $case_number, $id): void
    {
        global $res_path_xlsx;
        global $excelfile;

        // № п/п
        $excelfile->set_cell($res_path_xlsx, 0, $rowNum, 1, $numpp);

        // название файла
        $excelfile->set_cell($res_path_xlsx, 0, $rowNum, 2, $fileName);

        // дата скачивания
        $excelfile->set_cell($res_path_xlsx, 0, $rowNum, 3, $dl_date);

        // номер дела
        $excelfile->set_cell($res_path_xlsx, 0, $rowNum, 4, $case_number);

        // ид сообщения на ЕПГУ (который в ссылке)
        $excelfile->set_cell($res_path_xlsx, 0, $rowNum, 5, $id);
    }

    function updateResFileTwo($rowNum, $numpp, $url, $letter_date, $type, $fp, $status_file_dl): void
    {
        global $res_path_xlsx;
        global $excelfile;

        // № п/п
        $excelfile->set_cell($res_path_xlsx, 0, $rowNum, 1, $numpp);

        // адрес сообщения на ЕПГУ
        $excelfile->set_cell($res_path_xlsx, 0, $rowNum, 2, $url);

        // дата письма
        $excelfile->set_cell($res_path_xlsx, 0, $rowNum, 3, $letter_date);

        // ФЛ/ЮЛ
        $excelfile->set_cell($res_path_xlsx, 0, $rowNum, 4, $type);

        // Сетевой путь
        $excelfile->set_cell($res_path_xlsx, 0, $rowNum, 5, $fp);

        // Статус
        $excelfile->set_cell($res_path_xlsx, 0, $rowNum, 6, $status_file_dl);
    }
}