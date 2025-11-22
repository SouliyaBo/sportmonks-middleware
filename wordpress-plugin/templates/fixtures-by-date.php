<?php
/**
 * Template for displaying fixtures by date grouped by league
 * Variables available: $data (API response)
 */

if (!defined('ABSPATH')) {
    exit;
}

// ตรวจสอบข้อมูล
if (!isset($data['data']) || empty($data['data'])) {
    echo '<p class="smm-no-data">ไม่มีข้อมูลการแข่งขัน</p>';
    return;
}

$leagues = $data['data'];
$totalFixtures = isset($data['totalFixtures']) ? $data['totalFixtures'] : 0;
?>

<div class="smm-fixtures-by-date">
    <div class="smm-fixtures-header">
        <h2>โปรแกรมบอลวันนี้</h2>
        <?php if ($totalFixtures > 0): ?>
            <span class="smm-total-matches"><?php echo esc_html($totalFixtures); ?> นัด</span>
        <?php endif; ?>
    </div>

    <!-- ปุ่มเลือกวันที่ -->
    <div class="smm-date-selector">
        <?php
        $dates = array();
        for ($i = -1; $i <= 4; $i++) {
            $date = date('Y-m-d', strtotime("+$i days"));
            $thaiDate = date('j', strtotime($date));
            $thaiMonth = array('', 'ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.');
            $thaiYear = date('Y', strtotime($date)) + 543;
            $monthNum = (int)date('n', strtotime($date));
            
            $label = $thaiDate . ' ' . $thaiMonth[$monthNum] . ' ' . $thaiYear;
            $isActive = ($i === 0) ? 'active' : '';
            
            echo '<button class="smm-date-btn ' . $isActive . '" data-date="' . esc_attr($date) . '">';
            echo esc_html($label);
            echo '</button>';
        }
        ?>
    </div>

    <!-- แสดงแมตช์แยกตามลีก -->
    <div class="smm-fixtures-content">
        <?php foreach ($leagues as $league): ?>
            <div class="smm-league-group">
                <!-- หัวข้อลีก -->
                <div class="smm-league-header">
                    <?php if (isset($league['league']['image_path'])): ?>
                        <img src="<?php echo esc_url($league['league']['image_path']); ?>" 
                             alt="<?php echo esc_attr($league['league']['name']); ?>" 
                             class="smm-league-logo">
                    <?php endif; ?>
                    <h3 class="smm-league-name">
                        <?php echo esc_html($league['league']['name']); ?>
                        <?php if (isset($league['league']['country'])): ?>
                            <span class="smm-country"><?php echo esc_html($league['league']['country']); ?></span>
                        <?php endif; ?>
                    </h3>
                    <span class="smm-match-count">(<?php echo count($league['fixtures']); ?> คู่)</span>
                </div>

                <!-- รายการแมตช์ -->
                <div class="smm-fixtures-list">
                    <div class="smm-fixtures-table-header">
                        <div class="smm-col-time">เวลาเริ่ม</div>
                        <div class="smm-col-match">คู่การแข่งขัน</div>
                        <div class="smm-col-venue">สนาม</div>
                    </div>
                    
                    <?php foreach ($league['fixtures'] as $fixture): ?>
                        <?php
                        // แยกทีมเหย้าและเยือน
                        $homeTeam = null;
                        $awayTeam = null;
                        if (isset($fixture['participants']) && is_array($fixture['participants'])) {
                            foreach ($fixture['participants'] as $participant) {
                                if (isset($participant['meta']['location'])) {
                                    if ($participant['meta']['location'] === 'home') {
                                        $homeTeam = $participant;
                                    } else {
                                        $awayTeam = $participant;
                                    }
                                }
                            }
                        }
                        
                        // แปลงเวลา
                        $startTime = '';
                        if (isset($fixture['starting_at'])) {
                            $dt = new DateTime($fixture['starting_at']);
                            $startTime = $dt->format('H:i');
                        }
                        
                        // ดึงสกอร์ (ถ้ามี)
                        $homeScore = null;
                        $awayScore = null;
                        if (isset($fixture['scores']) && is_array($fixture['scores'])) {
                            foreach ($fixture['scores'] as $score) {
                                if ($score['description'] === 'CURRENT') {
                                    if ($score['score']['participant'] === 'home') {
                                        $homeScore = $score['score']['goals'];
                                    } else {
                                        $awayScore = $score['score']['goals'];
                                    }
                                }
                            }
                        }
                        
                        // สถานะการแข่งขัน
                        $stateClass = '';
                        $stateName = '';
                        if (isset($fixture['state']['short_name'])) {
                            $stateName = $fixture['state']['short_name'];
                            if ($stateName === 'FT') {
                                $stateClass = 'finished';
                            } elseif (in_array($stateName, ['1H', '2H', 'HT', 'LIVE'])) {
                                $stateClass = 'live';
                            }
                        }
                        ?>
                        
                        <div class="smm-fixture-row <?php echo esc_attr($stateClass); ?>">
                            <div class="smm-col-time">
                                <span class="smm-time"><?php echo esc_html($startTime); ?></span>
                                <?php if ($stateName): ?>
                                    <span class="smm-state <?php echo esc_attr($stateClass); ?>">
                                        <?php echo esc_html($stateName); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="smm-col-match">
                                <div class="smm-match-teams">
                                    <!-- ทีมเหย้า -->
                                    <div class="smm-team smm-home">
                                        <?php if ($homeTeam && isset($homeTeam['image_path'])): ?>
                                            <img src="<?php echo esc_url($homeTeam['image_path']); ?>" 
                                                 alt="<?php echo esc_attr($homeTeam['name']); ?>" 
                                                 class="smm-team-logo">
                                        <?php endif; ?>
                                        <span class="smm-team-name">
                                            <?php echo esc_html($homeTeam ? $homeTeam['name'] : 'TBD'); ?>
                                        </span>
                                    </div>
                                    
                                    <!-- VS หรือ สกอร์ -->
                                    <div class="smm-vs-score">
                                        <?php if ($homeScore !== null && $awayScore !== null): ?>
                                            <span class="smm-score">
                                                <?php echo esc_html($homeScore . ' - ' . $awayScore); ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="smm-vs">vs</span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <!-- ทีมเยือน -->
                                    <div class="smm-team smm-away">
                                        <?php if ($awayTeam && isset($awayTeam['image_path'])): ?>
                                            <img src="<?php echo esc_url($awayTeam['image_path']); ?>" 
                                                 alt="<?php echo esc_attr($awayTeam['name']); ?>" 
                                                 class="smm-team-logo">
                                        <?php endif; ?>
                                        <span class="smm-team-name">
                                            <?php echo esc_html($awayTeam ? $awayTeam['name'] : 'TBD'); ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="smm-col-venue">
                                <?php if (isset($fixture['venue']['name'])): ?>
                                    <span class="smm-venue-name">
                                        <?php echo esc_html($fixture['venue']['name']); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<style>
.smm-fixtures-by-date {
    background: #1a1f2e;
    border-radius: 12px;
    padding: 20px;
    margin: 20px 0;
    color: #fff;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
}

.smm-fixtures-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.smm-fixtures-header h2 {
    color: #fff;
    font-size: 1.8em;
    margin: 0;
}

.smm-total-matches {
    background: #ff6b00;
    color: #fff;
    padding: 8px 16px;
    border-radius: 20px;
    font-weight: bold;
}

/* ปุ่มเลือกวันที่ */
.smm-date-selector {
    display: flex;
    gap: 10px;
    margin-bottom: 25px;
    overflow-x: auto;
    padding-bottom: 10px;
}

.smm-date-btn {
    background: #2a3448;
    border: none;
    color: #fff;
    padding: 12px 20px;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.3s ease;
    white-space: nowrap;
    font-size: 0.95em;
}

.smm-date-btn:hover {
    background: #3a4558;
}

.smm-date-btn.active {
    background: #ff6b00;
    font-weight: bold;
}

/* กลุ่มลีก */
.smm-league-group {
    background: #242b3d;
    border-radius: 10px;
    margin-bottom: 20px;
    overflow: hidden;
}

.smm-league-header {
    background: #2a3448;
    padding: 15px 20px;
    display: flex;
    align-items: center;
    gap: 12px;
    border-bottom: 2px solid #ff6b00;
}

.smm-league-logo {
    width: 30px;
    height: 30px;
    object-fit: contain;
}

.smm-league-name {
    margin: 0;
    font-size: 1.2em;
    flex: 1;
    display: flex;
    align-items: center;
    gap: 10px;
}

.smm-country {
    font-size: 0.8em;
    color: #8899aa;
    font-weight: normal;
}

.smm-match-count {
    color: #8899aa;
    font-size: 0.9em;
}

/* ตารางแมตช์ */
.smm-fixtures-list {
    padding: 10px;
}

.smm-fixtures-table-header {
    display: grid;
    grid-template-columns: 120px 1fr 200px;
    gap: 15px;
    padding: 10px 15px;
    color: #8899aa;
    font-size: 0.9em;
    font-weight: 600;
    border-bottom: 1px solid #3a4558;
}

.smm-fixture-row {
    display: grid;
    grid-template-columns: 120px 1fr 200px;
    gap: 15px;
    padding: 15px;
    border-bottom: 1px solid #2a3448;
    transition: background 0.2s ease;
}

.smm-fixture-row:hover {
    background: #2a3448;
}

.smm-fixture-row:last-child {
    border-bottom: none;
}

/* คอลัมน์เวลา */
.smm-col-time {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.smm-time {
    font-size: 1.1em;
    font-weight: bold;
    color: #fff;
}

.smm-state {
    font-size: 0.75em;
    padding: 3px 8px;
    border-radius: 4px;
    width: fit-content;
}

.smm-state.live {
    background: #ff0000;
    color: #fff;
    animation: pulse 1.5s infinite;
}

.smm-state.finished {
    background: #4a5568;
    color: #fff;
}

@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.7; }
}

/* คู่การแข่งขัน */
.smm-match-teams {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 15px;
}

.smm-team {
    display: flex;
    align-items: center;
    gap: 10px;
    flex: 1;
}

.smm-team.smm-home {
    justify-content: flex-end;
    text-align: right;
}

.smm-team.smm-away {
    justify-content: flex-start;
}

.smm-team-logo {
    width: 28px;
    height: 28px;
    object-fit: contain;
}

.smm-team-name {
    font-size: 1em;
    color: #fff;
    font-weight: 500;
}

.smm-vs-score {
    min-width: 60px;
    text-align: center;
}

.smm-vs {
    color: #8899aa;
    font-weight: bold;
    font-size: 0.9em;
}

.smm-score {
    font-size: 1.2em;
    font-weight: bold;
    color: #ff6b00;
}

/* สนาม */
.smm-col-venue {
    display: flex;
    align-items: center;
}

.smm-venue-name {
    color: #8899aa;
    font-size: 0.9em;
}

.smm-no-data {
    text-align: center;
    padding: 40px;
    color: #8899aa;
    font-style: italic;
}

/* Responsive */
@media (max-width: 1024px) {
    .smm-fixtures-table-header,
    .smm-fixture-row {
        grid-template-columns: 100px 1fr 150px;
    }
    
    .smm-team-name {
        font-size: 0.9em;
    }
}

@media (max-width: 768px) {
    .smm-fixtures-table-header {
        display: none;
    }
    
    .smm-fixture-row {
        grid-template-columns: 1fr;
        gap: 10px;
    }
    
    .smm-col-time {
        flex-direction: row;
        justify-content: space-between;
        align-items: center;
    }
    
    .smm-match-teams {
        flex-direction: column;
        gap: 10px;
    }
    
    .smm-team {
        width: 100%;
    }
    
    .smm-team.smm-home {
        justify-content: flex-start;
        text-align: left;
    }
    
    .smm-vs-score {
        width: 100%;
    }
    
    .smm-col-venue {
        justify-content: center;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    // ฟังก์ชันเปลี่ยนวันที่
    $('.smm-date-btn').on('click', function() {
        var selectedDate = $(this).data('date');
        var $container = $(this).closest('.smm-fixtures-by-date');
        
        // เปลี่ยน active state
        $('.smm-date-btn').removeClass('active');
        $(this).addClass('active');
        
        // โหลดข้อมูลใหม่
        $container.find('.smm-fixtures-content').html('<div style="text-align:center;padding:40px;color:#8899aa;">กำลังโหลด...</div>');
        
        // Reload page with new date parameter
        var currentUrl = window.location.href.split('?')[0];
        window.location.href = currentUrl + '?date=' + selectedDate;
    });
});
</script>
