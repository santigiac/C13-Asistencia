<?php
/**
 * Clase Exporter - Exportar datos a CSV
 */

class Exporter {

    /**
     * Exportar un array de datos como CSV y forzar la descarga
     * @param array $data  Array de arrays asociativos
     * @param string $filename  Nombre del archivo
     * @param array $headers  Cabeceras del CSV (opcional, se deducen del primer registro)
     */
    public static function toCSV($data, $filename = 'export.csv', $headers = []) {
        if (empty($data)) {
            return;
        }

        // Limpiar buffers
        if (ob_get_level()) {
            ob_end_clean();
        }

        // Headers HTTP para forzar descarga
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $output = fopen('php://output', 'w');
        
        // BOM para que Excel reconozca UTF-8
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

        // Cabeceras
        if (empty($headers)) {
            $headers = array_keys($data[0]);
        }
        fputcsv($output, $headers, ';');

        // Datos
        foreach ($data as $row) {
            $values = [];
            foreach ($headers as $header) {
                $values[] = $row[$header] ?? '';
            }
            fputcsv($output, $values, ';');
        }

        fclose($output);
        exit;
    }

    /**
     * Exportar asistencia de un grupo para un mes
     */
    public static function exportAttendanceReport($groupId, $yearMonth) {
        $db = getDB();

        // Obtener alumnos del grupo
        $stmt = $db->prepare("
            SELECT s.id, s.name, s.surname, 
                   u.name AS parent_name, u.phone AS parent_phone
            FROM students s
            LEFT JOIN users u ON u.id = s.parent_id
            WHERE s.group_id = :group_id
            ORDER BY s.surname, s.name
        ");
        $stmt->execute([':group_id' => $groupId]);
        $students = $stmt->fetchAll();

        // Obtener asistencia del mes
        $stmt = $db->prepare("
            SELECT a.student_id, a.date, a.status
            FROM attendance a
            JOIN students s ON s.id = a.student_id
            WHERE s.group_id = :group_id 
            AND DATE_FORMAT(a.date, '%Y-%m') = :month
        ");
        $stmt->execute([':group_id' => $groupId, ':month' => $yearMonth]);
        $attendance = $stmt->fetchAll();

        // Organizar por alumno y fecha
        $attendanceMap = [];
        $dates = [];
        foreach ($attendance as $record) {
            $attendanceMap[$record['student_id']][$record['date']] = $record['status'];
            $dates[$record['date']] = true;
        }
        ksort($dates);
        $dateList = array_keys($dates);

        // Construir datos CSV
        $headers = ['Alumno', 'Padre/Madre', 'Teléfono'];
        foreach ($dateList as $d) {
            $headers[] = date('d/m', strtotime($d));
        }
        $headers[] = 'Total Presencias';
        $headers[] = 'Total Ausencias';

        $data = [];
        foreach ($students as $student) {
            $row = [
                'Alumno'     => $student['surname'] . ', ' . $student['name'],
                'Padre/Madre' => $student['parent_name'] ?? '-',
                'Teléfono'   => $student['parent_phone'] ?? '-',
            ];
            $present = 0;
            $absent = 0;
            foreach ($dateList as $d) {
                $status = $attendanceMap[$student['id']][$d] ?? '-';
                $label = '-';
                switch ($status) {
                    case 'present':   $label = 'P'; $present++; break;
                    case 'absent':    $label = 'A'; $absent++;  break;
                    case 'late':      $label = 'R'; $present++; break;
                    case 'justified': $label = 'J'; break;
                    default:          $label = '-'; break;
                }
                $row[date('d/m', strtotime($d))] = $label;
            }
            $row['Total Presencias'] = $present;
            $row['Total Ausencias']  = $absent;
            $data[] = $row;
        }

        // Obtener nombre del grupo
        $stmt = $db->prepare("SELECT name FROM `groups` WHERE id = :id");
        $stmt->execute([':id' => $groupId]);
        $group = $stmt->fetch();
        $groupName = $group ? $group['name'] : 'grupo';

        $filename = "asistencia_{$groupName}_{$yearMonth}.csv";
        self::toCSV($data, $filename, $headers);
    }
}
