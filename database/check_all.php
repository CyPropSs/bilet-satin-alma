<?php
include '../includes/config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] == 'update') {
        $table = $_POST['table'];
        $column = $_POST['column'];
        $value = $_POST['value'];
        $id = $_POST['id'];
        $id_column = $_POST['id_column'];
        
        try {
            $stmt = $db->prepare("UPDATE $table SET $column = ? WHERE $id_column = ?");
            $stmt->execute([$value, $id]);
            
            echo json_encode(['success' => true, 'message' => 'Veri başarıyla güncellendi!']);
            exit();
            
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Güncelleme hatası: ' . $e->getMessage()]);
            exit();
        }
    }
}

function formatValue($value, $column) {
    if ($value === null) {
        return '<span class="text-muted fst-italic">NULL</span>';
    }
    if (empty($value) && $value !== '0') {
        return '<span class="text-muted">-</span>';
    }
    if ($column === 'password') {
        return '<span class="text-danger fw-bold">••••••••</span>';
    }
    if ($column === 'balance') {
        return '<span class="balance-cell">' . htmlspecialchars($value) . '</span>';
    }
    if (in_array($column, ['id', 'company_id', 'trip_id', 'user_id', 'coupon_id', 'ticket_id']) && strlen($value) > 10) {
        return '<span class="uuid-full" title="UUID">' . htmlspecialchars($value) . '</span>';
    }
    if (preg_match('/^(created_at|departure_time|arrival_time|expire_date|created_date)/', $column) && strtotime($value)) {
        return date('d.m.Y H:i', strtotime($value));
    }
    return htmlspecialchars($value);
}

echo "<!DOCTYPE html>
<html lang='tr'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Veritabanı Kontrol Paneli - Düzenlenebilir</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
    <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css'>
    <style>
        .table-container {
            margin-bottom: 30px;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            overflow: hidden;
        }
        .table-title {
            background-color: #343a40;
            color: white;
            padding: 10px 15px;
            margin: 0;
            cursor: pointer;
        }
        .badge-count {
            background-color: #17a2b8;
            color: white;
            padding: 3px 8px;
            border-radius: 10px;
            font-size: 0.8em;
        }
        .action-buttons {
            margin-bottom: 20px;
        }
        .schema-table {
            font-size: 0.9em;
        }
        .uuid-full {
            font-family: monospace;
            background-color: #f8f9fa;
            padding: 4px 8px;
            border-radius: 3px;
            font-size: 0.8em;
            word-break: break-all;
        }
        .editable {
            cursor: pointer;
            transition: background-color 0.2s;
        }
        .editable:hover {
            background-color: #fff3cd !important;
        }
        .editing {
            background-color: #d1ecf1 !important;
        }
        .edit-input {
            width: 100%;
            border: 1px solid #007bff;
            border-radius: 3px;
            padding: 4px 8px;
            font-size: 0.85em;
        }
        .save-btn {
            padding: 2px 8px;
            font-size: 0.7em;
            margin-left: 5px;
        }
        .balance-cell {
            font-weight: bold;
            color: #28a745;
        }
        .balance-cell.editing {
            color: #007bff;
        }
        .text-cell {
            max-width: 300px;
            word-wrap: break-word;
        }
        .table-responsive {
            overflow-x: auto;
        }
        .table td {
            vertical-align: middle;
        }
        .null-value {
            color: #6c757d;
            font-style: italic;
        }
    </style>
</head>
<body>
    <div class='container-fluid mt-4'>
        <h1 class='mb-4'><i class='fas fa-database'></i> Veritabanı Kontrol Paneli - Düzenlenebilir</h1>
        
        <div class='alert alert-info'>
            <i class='fas fa-edit'></i> <strong>Düzenleme Özelliği:</strong> Balance ve diğer hücrelere tıklayarak değerleri değiştirebilirsiniz.
        </div>
        
        <div class='action-buttons'>
           
        </div>";

try {
    $tables_stmt = $db->query("SELECT name FROM sqlite_master WHERE type='table' ORDER BY name");
    $tables = $tables_stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($tables)) {
        echo "<div class='alert alert-warning'>❌ Hiç tablo bulunamadı!</div>";
    } else {
        echo "<div class='alert alert-info'>
                <i class='fas fa-info-circle'></i> Toplam <strong>" . count($tables) . "</strong> tablo bulundu.
              </div>";
        
        echo "<div class='table-container'>
                <h3 class='table-title'><i class='fas fa-info-circle'></i> Tablo Şemaları</h3>
                <div class='table-responsive'>";

        foreach($tables as $table) {
            echo "<h5 class='mt-3 ms-3'>" . htmlspecialchars($table) . " Tablosu</h5>";
            
            $schema_stmt = $db->query("PRAGMA table_info(" . $table . ")");
            $schema = $schema_stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (!empty($schema)) {
                echo "<table class='table table-sm table-bordered schema-table'>
                        <thead class='table-secondary'>
                            <tr>
                                <th>Sütun Adı</th>
                                <th>Veri Tipi</th>
                                <th>NULL</th>
                                <th>Varsayılan</th>
                                <th>Primary Key</th>
                                <th>Düzenlenebilir</th>
                            </tr>
                        </thead>
                        <tbody>";
                
                foreach($schema as $column) {
                    $editable = !in_array($column['name'], ['id', 'created_at']) && $column['pk'] == 0;
                    echo "<tr>
                            <td><strong>" . htmlspecialchars($column['name']) . "</strong></td>
                            <td><code>" . htmlspecialchars($column['type']) . "</code></td>
                            <td>" . ($column['notnull'] ? '❌' : '✅') . "</td>
                            <td>" . ($column['dflt_value'] ? '<code>' . htmlspecialchars($column['dflt_value']) . '</code>' : '<span class=\"text-muted\">-</span>') . "</td>
                            <td>" . ($column['pk'] ? '✅' : '❌') . "</td>
                            <td>" . ($editable ? '✅' : '❌') . "</td>
                          </tr>";
                }
                
                echo "</tbody>
                      </table>";
            }
        }

        echo "</div>
              </div>";

        foreach($tables as $table) {
            $data_stmt = $db->query("SELECT * FROM " . $table);
            $data = $data_stmt->fetchAll(PDO::FETCH_ASSOC);
            $row_count = count($data);
            
            echo "<div class='table-container'>
                    <h3 class='table-title'>
                        <i class='fas fa-table'></i> " . htmlspecialchars($table) . " 
                        <span class='badge-count'>" . $row_count . " kayıt</span>
                    </h3>
                    <div class='table-responsive'>";
            
            if ($row_count > 0) {
                echo "<table class='table table-striped table-bordered table-hover table-sm' data-table='" . htmlspecialchars($table) . "'>
                        <thead class='table-dark'>
                            <tr>";
                
                $columns = array_keys($data[0]);
                $id_column = $columns[0]; // İlk sütunu ID olarak kabul et
                
                foreach($columns as $column) {
                    echo "<th>" . htmlspecialchars($column) . "</th>";
                }
                
                echo "</tr>
                        </thead>
                        <tbody>";
                
                foreach($data as $row) {
                    echo "<tr data-row-id='" . htmlspecialchars($row[$id_column]) . "'>";
                    foreach($row as $key => $value) {
                        $is_editable = !in_array($key, ['id', 'created_at']) && $key != $id_column;
                        $is_balance = $key === 'balance';
                        $is_id = in_array($key, ['id', 'company_id', 'trip_id', 'user_id', 'coupon_id', 'ticket_id']);
                        $cell_class = $is_balance ? 'balance-cell' : '';
                        
                        echo "<td class='" . $cell_class . " " . ($is_editable ? 'editable' : '') . " text-cell' 
                                  data-column='" . htmlspecialchars($key) . "' 
                                  data-original-value='" . htmlspecialchars($value ?? '') . "'>";
                        
                        echo formatValue($value, $key);
                        
                        echo "</td>";
                    }
                    echo "</tr>";
                }
                
                echo "</tbody>
                    </table>";
            } else {
                echo "<div class='p-3 text-center text-muted'>
                        <i class='fas fa-inbox'></i> Bu tabloda henüz veri bulunmuyor.
                      </div>";
            }
            
            echo "</div>
                  </div>";
        }
    }
    
} catch (PDOException $e) {
    echo "<div class='alert alert-danger'>❌ Veritabanı hatası: " . $e->getMessage() . "</div>";
}

echo "</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let currentEditingCell = null;
    
    document.addEventListener('click', function(e) {
        const cell = e.target.closest('.editable');
        if (cell && !currentEditingCell) {
            startEditing(cell);
        }
    });
    
    function startEditing(cell) {
        if (currentEditingCell) {
            cancelEditing(currentEditingCell);
        }
        
        currentEditingCell = cell;
        const originalValue = cell.getAttribute('data-original-value');
        const column = cell.getAttribute('data-column');
        const row = cell.closest('tr');
        const table = row.closest('table').getAttribute('data-table');
        const rowId = row.getAttribute('data-row-id');
        const idColumn = row.cells[0].getAttribute('data-column');
        
        const input = document.createElement('input');
        input.type = 'text';
        input.className = 'edit-input';
        input.value = originalValue;
        
        const saveBtn = document.createElement('button');
        saveBtn.className = 'btn btn-success btn-sm save-btn';
        saveBtn.innerHTML = '<i class=\"fas fa-check\"></i>';
        saveBtn.onclick = function() {
            saveEditing(cell, input.value, table, column, rowId, idColumn);
        };
        
        const cancelBtn = document.createElement('button');
        cancelBtn.className = 'btn btn-danger btn-sm save-btn';
        cancelBtn.innerHTML = '<i class=\"fas fa-times\"></i>';
        cancelBtn.onclick = function() {
            cancelEditing(cell);
        };
        
        cell.innerHTML = '';
        cell.appendChild(input);
        cell.appendChild(saveBtn);
        cell.appendChild(cancelBtn);
        cell.classList.add('editing');
        
        input.focus();
        input.select();
        
        input.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                saveEditing(cell, input.value, table, column, rowId, idColumn);
            } else if (e.key === 'Escape') {
                cancelEditing(cell);
            }
        });
    }
    
    function saveEditing(cell, newValue, table, column, rowId, idColumn) {
        fetch('check_all.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=update&table=' + encodeURIComponent(table) + 
                  '&column=' + encodeURIComponent(column) + 
                  '&value=' + encodeURIComponent(newValue) + 
                  '&id=' + encodeURIComponent(rowId) + 
                  '&id_column=' + encodeURIComponent(idColumn)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                cell.innerHTML = newValue; // Basit gösterim
                cell.setAttribute('data-original-value', newValue);
                
                if (column === 'balance') {
                    cell.innerHTML = '<span class=\"balance-cell\">' + newValue + '</span>';
                }
                
                cell.classList.remove('editing');
                currentEditingCell = null;
                
                showMessage('Veri başarıyla güncellendi!', 'success');
            } else {
                alert('Güncelleme hatası: ' + data.message);
                cancelEditing(cell);
            }
        })
        .catch(error => {
            alert('Ağ hatası: ' + error);
            cancelEditing(cell);
        });
    }
    
    function cancelEditing(cell) {
        const originalValue = cell.getAttribute('data-original-value');
        const column = cell.getAttribute('data-column');
        
        if (originalValue === 'null' || originalValue === '') {
            cell.innerHTML = '<span class=\"null-value\">NULL</span>';
        } else if (column === 'balance') {
            cell.innerHTML = '<span class=\"balance-cell\">' + originalValue + '</span>';
        } else {
            cell.innerHTML = originalValue;
        }
        
        cell.classList.remove('editing');
        currentEditingCell = null;
    }
    
    function showMessage(message, type) {
        const alert = document.createElement('div');
        alert.className = 'alert alert-' + (type === 'success' ? 'success' : 'danger') + ' alert-dismissible fade show position-fixed top-0 end-0 m-3';
        alert.style.zIndex = '9999';
        alert.innerHTML = '<i class=\"fas fa-' + (type === 'success' ? 'check' : 'exclamation-triangle') + '\"></i> ' + message + 
                         '<button type=\"button\" class=\"btn-close\" data-bs-dismiss=\"alert\"></button>';
        document.body.appendChild(alert);
        
        setTimeout(() => {
            alert.remove();
        }, 3000);
    }
    
    const tableTitles = document.querySelectorAll('.table-title');
    tableTitles.forEach(title => {
        title.addEventListener('click', function() {
            const tableContainer = this.parentElement;
            const tableBody = tableContainer.querySelector('.table-responsive');
            if (tableBody.style.display === 'none') {
                tableBody.style.display = 'block';
            } else {
                tableBody.style.display = 'none';
            }
        });
    });
});
</script>

</body>
</html>";