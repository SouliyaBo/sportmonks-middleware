<?php
/**
 * Template for displaying schedules (โปรแกรมการแข่งขัน)
 * Variables available: $data (API response)
 */

if (!defined('ABSPATH')) {
    exit;
}

// ตรวจสอบข้อมูล
if (!isset($data['data']) || empty($data['data'])) {
    echo '<p class="smm-no-data">ไม่มีข้อมูลตารางการแข่งขัน</p>';
    return;
}

$schedules = $data['data'];
?>

<div class="smm-schedules-container">
    <div class="smm-schedules-header">
        <h3>📅 ตารางการแข่งขัน</h3>
        <?php if (isset($data['count'])): ?>
            <span class="smm-count">(<?php echo esc_html($data['count']); ?> นัด)</span>
        <?php endif; ?>
    </div>
    
    <div class="smm-schedules-list">
        <?php 
        // จัดกลุ่มตามรอบการแข่งขัน (ถ้ามี)
        $grouped = array();
        foreach ($schedules as $schedule) {
            // ตรวจสอบโครงสร้างข้อมูลจาก SportMonks Schedules API
            if (isset($schedule['round'])) {
                $round = $schedule['round']['name'] ?? 'อื่นๆ';
            } else {
                $round = 'ทั้งหมด';
            }
            
            if (!isset($grouped[$round])) {
                $grouped[$round] = array();
            }
            
            $grouped[$round][] = $schedule;
        }
        
        // แสดงผลแยกตามรอบ
        foreach ($grouped as $round_name => $round_schedules): ?>
            <div class="smm-schedule-round">
                <h4 class="smm-round-title"><?php echo esc_html($round_name); ?></h4>
                
                <div class="smm-schedule-items">
                    <?php foreach ($round_schedules as $item): ?>
                        <div class="smm-schedule-item">
                            <?php if (isset($item['fixtures']) && is_array($item['fixtures'])): ?>
                                <?php foreach ($item['fixtures'] as $fixture): ?>
                                    <div class="smm-fixture">
                                        <div class="smm-fixture-date">
                                            <?php 
                                            $date = isset($fixture['starting_at']) ? $fixture['starting_at'] : '';
                                            if ($date) {
                                                $dt = new DateTime($date);
                                                echo esc_html($dt->format('d/m/Y H:i'));
                                            }
                                            ?>
                                        </div>
                                        
                                        <div class="smm-fixture-teams">
                                            <div class="smm-team smm-home">
                                                <span class="smm-team-name">
                                                    <?php 
                                                    $home_team = $fixture['participants'][0]['name'] ?? 'TBD';
                                                    echo esc_html($home_team);
                                                    ?>
                                                </span>
                                            </div>
                                            
                                            <div class="smm-vs">VS</div>
                                            
                                            <div class="smm-team smm-away">
                                                <span class="smm-team-name">
                                                    <?php 
                                                    $away_team = $fixture['participants'][1]['name'] ?? 'TBD';
                                                    echo esc_html($away_team);
                                                    ?>
                                                </span>
                                            </div>
                                        </div>
                                        
                                        <?php if (isset($fixture['venue']['name'])): ?>
                                            <div class="smm-venue">
                                                📍 <?php echo esc_html($fixture['venue']['name']); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="smm-schedule-info">
                                    <?php 
                                    // กรณีข้อมูล schedule เป็นรูปแบบอื่น
                                    if (isset($item['name'])) {
                                        echo '<p>' . esc_html($item['name']) . '</p>';
                                    }
                                    if (isset($item['starting_at'])) {
                                        $dt = new DateTime($item['starting_at']);
                                        echo '<p class="smm-date">วันที่: ' . esc_html($dt->format('d/m/Y H:i')) . '</p>';
                                    }
                                    ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    
    <div class="smm-schedules-footer">
        <p class="smm-last-updated">
            อัปเดตล่าสุด: <?php echo current_time('d/m/Y H:i'); ?>
        </p>
    </div>
</div>

<style>
.smm-schedules-container {
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    padding: 20px;
    margin: 20px 0;
}

.smm-schedules-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 2px solid #3b5998;
    padding-bottom: 10px;
    margin-bottom: 20px;
}

.smm-schedules-header h3 {
    margin: 0;
    color: #3b5998;
    font-size: 1.5em;
}

.smm-count {
    background: #3b5998;
    color: white;
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 0.9em;
}

.smm-schedule-round {
    margin-bottom: 30px;
}

.smm-round-title {
    background: #f0f2f5;
    padding: 10px 15px;
    margin: 0 0 15px 0;
    border-left: 4px solid #3b5998;
    color: #333;
    font-size: 1.1em;
}

.smm-schedule-items {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.smm-schedule-item {
    background: #f9f9f9;
    border-radius: 6px;
    padding: 15px;
    border-left: 3px solid #ddd;
}

.smm-fixture {
    background: white;
    padding: 15px;
    border-radius: 6px;
    margin-bottom: 10px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.smm-fixture:last-child {
    margin-bottom: 0;
}

.smm-fixture-date {
    color: #888;
    font-size: 0.9em;
    margin-bottom: 10px;
    font-weight: 500;
}

.smm-fixture-teams {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 15px;
    margin-bottom: 10px;
}

.smm-team {
    flex: 1;
    text-align: center;
}

.smm-team-name {
    font-weight: 600;
    color: #333;
    font-size: 1em;
}

.smm-vs {
    background: #3b5998;
    color: white;
    padding: 5px 15px;
    border-radius: 4px;
    font-weight: bold;
    font-size: 0.8em;
}

.smm-venue {
    color: #666;
    font-size: 0.85em;
    text-align: center;
    padding-top: 8px;
    border-top: 1px solid #eee;
}

.smm-schedule-info p {
    margin: 5px 0;
    color: #555;
}

.smm-schedules-footer {
    margin-top: 20px;
    padding-top: 15px;
    border-top: 1px solid #eee;
    text-align: center;
}

.smm-last-updated {
    color: #999;
    font-size: 0.85em;
    margin: 0;
}

.smm-no-data {
    text-align: center;
    padding: 40px;
    color: #999;
    font-style: italic;
}

.smm-error {
    background: #fee;
    border: 1px solid #fcc;
    color: #c33;
    padding: 15px;
    border-radius: 6px;
    text-align: center;
}

/* Responsive */
@media (max-width: 768px) {
    .smm-schedules-container {
        padding: 15px;
    }
    
    .smm-schedules-header h3 {
        font-size: 1.2em;
    }
    
    .smm-fixture-teams {
        flex-direction: column;
        gap: 10px;
    }
    
    .smm-team {
        width: 100%;
    }
    
    .smm-vs {
        padding: 5px 10px;
    }
}
</style>
