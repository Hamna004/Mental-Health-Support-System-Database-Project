<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - MindCare</title>
    <style>
        * {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    padding: 20px;
    min-height: 100vh;
}

.dashboard-container {
    max-width: 1400px;
    margin: 0 auto;
}

.dashboard-header {
    background: white;
    padding: 30px;
    border-radius: 12px;
    box-shadow: 0 8px 25px rgba(0,0,0,0.1);
    margin-bottom: 30px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-left: 5px solid #6366f1;
}

.dashboard-header h1 {
    font-size: 32px;
    color: #333;
    margin-bottom: 8px;
    background: linear-gradient(135deg, #6366f1, #8b5cf6);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.dashboard-header p {
    color: #666;
    font-size: 16px;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 25px;
    margin-bottom: 30px;
}

.stat-card {
    background: white;
    padding: 30px;
    border-radius: 12px;
    box-shadow: 0 8px 25px rgba(0,0,0,0.1);
    text-align: center;
    transition: transform 0.3s, box-shadow 0.3s;
    border-top: 4px solid #6366f1;
}

.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 12px 30px rgba(0,0,0,0.15);
}

.stat-card h3 {
    font-size: 42px;
    background: linear-gradient(135deg, #6366f1, #8b5cf6);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    margin-bottom: 10px;
}

.stat-card p {
    color: #666;
    font-size: 16px;
    font-weight: 500;
}

.tabs-container {
    background: white;
    border-radius: 12px;
    box-shadow: 0 8px 25px rgba(0,0,0,0.1);
    overflow: hidden;
}

.tabs-header {
    display: flex;
    border-bottom: 2px solid #e5e7eb;
    background: linear-gradient(135deg, #f8fafc, #f1f5f9);
}

.tab-button {
    flex: 1;
    padding: 20px 24px;
    background: transparent;
    border: none;
    cursor: pointer;
    font-size: 16px;
    font-weight: 600;
    color: #666;
    transition: all 0.3s;
    border-bottom: 4px solid transparent;
    position: relative;
    overflow: hidden;
}

.tab-button:hover {
    background: rgba(99, 102, 241, 0.1);
    color: #6366f1;
}

.tab-button.active {
    color: #6366f1;
    border-bottom-color: #6366f1;
    background: white;
    box-shadow: 0 2px 10px rgba(99, 102, 241, 0.2);
}

.tab-content {
    display: none;
    padding: 30px;
}

.tab-content.active {
    display: block;
}

.data-table {
    width: 100%;
    border-collapse: collapse;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    border-radius: 8px;
    overflow: hidden;
}

.data-table th {
    background: linear-gradient(135deg, #6366f1, #8b5cf6);
    padding: 18px;
    text-align: left;
    font-weight: 600;
    color: white;
    border: none;
}

.data-table td {
    padding: 18px;
    border-bottom: 1px solid #e5e7eb;
    color: #555;
    background: white;
}

.data-table tr:hover {
    background: #f8fafc;
}

.data-table tr:nth-child(even) {
    background: #f9fafb;
}

.data-table tr:nth-child(even):hover {
    background: #f1f5f9;
}

.role-badge {
    display: inline-block;
    padding: 8px 16px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.role-PATIENT {
    background: linear-gradient(135deg, #3b82f6, #1d4ed8);
    color: white;
}

.role-THERAPIST {
    background: linear-gradient(135deg, #10b981, #047857);
    color: white;
}

.role-ADMIN {
    background: linear-gradient(135deg, #f59e0b, #d97706);
    color: white;
}

.mood-badge {
    display: inline-block;
    padding: 8px 16px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.mood-HAPPY { background: linear-gradient(135deg, #fbbf24, #d97706); color: white; }
.mood-SAD { background: linear-gradient(135deg, #60a5fa, #1d4ed8); color: white; }
.mood-ANXIOUS { background: linear-gradient(135deg, #f472b6, #db2777); color: white; }
.mood-STRESSED { background: linear-gradient(135deg, #ef4444, #dc2626); color: white; }
.mood-CALM { background: linear-gradient(135deg, #10b981, #047857); color: white; }
.mood-ANGRY { background: linear-gradient(135deg, #dc2626, #991b1b); color: white; }
.mood-TIRED { background: linear-gradient(135deg, #8b5cf6, #7c3aed); color: white; }
.mood-EXCITED { background: linear-gradient(135deg, #eab308, #ca8a04); color: white; }

.intensity-bar {
    display: inline-block;
    width: 120px;
    height: 12px;
    background: #e5e7eb;
    border-radius: 6px;
    position: relative;
    vertical-align: middle;
    margin-right: 10px;
}

.intensity-fill {
    height: 100%;
    background: linear-gradient(90deg, #10b981, #eab308, #ef4444);
    border-radius: 6px;
    transition: width 0.5s ease;
}

.status-badge {
    display: inline-block;
    padding: 8px 16px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.status-BOOKED { background: linear-gradient(135deg, #3b82f6, #1d4ed8); color: white; }
.status-COMPLETED { background: linear-gradient(135deg, #10b981, #047857); color: white; }
.status-CANCELLED { background: linear-gradient(135deg, #ef4444, #dc2626); color: white; }
.status-NO-SHOW { background: linear-gradient(135deg, #f59e0b, #d97706); color: white; }

.status-active {
    color: #10b981;
    font-weight: 600;
    background: #d1fae5;
    padding: 6px 12px;
    border-radius: 15px;
}

.status-inactive {
    color: #ef4444;
    font-weight: 600;
    background: #fee2e2;
    padding: 6px 12px;
    border-radius: 15px;
}

.logout-btn {
    background: linear-gradient(135deg, #ef4444, #dc2626);
    color: white;
    padding: 14px 28px;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-size: 16px;
    font-weight: 600;
    transition: all 0.3s;
    box-shadow: 0 4px 15px rgba(239, 68, 68, 0.3);
}

.logout-btn:hover {
    background: linear-gradient(135deg, #dc2626, #b91c1c);
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(239, 68, 68, 0.4);
}

.loading {
    text-align: center;
    padding: 50px;
    font-size: 18px;
    color: #666;
    background: white;
    border-radius: 12px;
    box-shadow: 0 8px 25px rgba(0,0,0,0.1);
}

.no-data {
    text-align: center;
    padding: 40px;
    color: #999;
    font-style: italic;
    background: #f9fafb;
    border-radius: 8px;
}

.period-toggle {
    display: flex;
    gap: 15px;
    margin-bottom: 25px;
}

.period-btn {
    padding: 14px 28px;
    border: none;
    background: linear-gradient(135deg, #e5e7eb, #d1d5db);
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
    font-size: 14px;
    transition: all 0.3s;
    color: #6b7280;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.period-btn:hover {
    background: linear-gradient(135deg, #d1d5db, #9ca3af);
    color: #374151;
    transform: translateY(-2px);
}

.period-btn.active {
    background: linear-gradient(135deg, #6366f1, #8b5cf6);
    color: white;
    box-shadow: 0 4px 15px rgba(99, 102, 241, 0.4);
    transform: translateY(-2px);
}

.user-summary-section {
    background: linear-gradient(135deg, #f8fafc, #f1f5f9);
    padding: 25px;
    border-radius: 12px;
    margin-bottom: 30px;
    border-left: 4px solid #6366f1;
}

.user-summary-section label {
    display: block;
    margin-bottom: 12px;
    font-weight: 600;
    color: #374151;
    font-size: 16px;
}

.user-summary-section select {
    width: 100%;
    padding: 14px;
    border: 2px solid #e5e7eb;
    border-radius: 8px;
    font-size: 16px;
    background: white;
    transition: border-color 0.3s;
}

.user-summary-section select:focus {
    outline: none;
    border-color: #6366f1;
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
}

.summary-cards-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
    margin-bottom: 25px;
}

.summary-card-small {
    background: white;
    padding: 20px;
    border-radius: 8px;
    text-align: center;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    transition: transform 0.3s;
    border-top: 3px solid #6366f1;
}

.summary-card-small:hover {
    transform: translateY(-3px);
}

.summary-card-small h4 {
    font-size: 28px;
    background: linear-gradient(135deg, #6366f1, #8b5cf6);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    margin: 0 0 8px 0;
}

.summary-card-small p {
    margin: 0;
    color: #666;
    font-size: 14px;
    font-weight: 500;
}

.mood-distribution-admin {
    background: white;
    padding: 25px;
    border-radius: 8px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.mood-distribution-admin h3 {
    margin: 0 0 20px 0;
    color: #374151;
    font-size: 20px;
}

@media (max-width: 1200px) {
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    .summary-cards-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 768px) {
    .stats-grid {
        grid-template-columns: 1fr;
    }
    .summary-cards-grid {
        grid-template-columns: 1fr;
    }
    .tabs-header {
        flex-direction: column;
    }
    .tab-button {
        padding: 16px 20px;
    }
    .period-toggle {
        flex-direction: column;
    }
}
    </style>
</head>
<body>
    <div class="dashboard-container">
        <div class="dashboard-header">
            <div>
                <h1>Admin Dashboard</h1>
                <p id="welcome-text">Welcome, Admin</p>
            </div>
            <button class="logout-btn" onclick="logout()">Logout</button>
        </div>
        
        <div id="loading" class="loading">Loading dashboard data...</div>
        
        <div id="dashboard-content" style="display: none;">
            <div class="stats-grid">
                <div class="stat-card">
                    <h3 id="stat-users">0</h3>
                    <p>Total Users</p>
                </div>
                <div class="stat-card">
                    <h3 id="stat-patients">0</h3>
                    <p>Patients</p>
                </div>
                <div class="stat-card">
                    <h3 id="stat-therapists">0</h3>
                    <p>Therapists</p>
                </div>
                <div class="stat-card">
                    <h3 id="stat-sessions">0</h3>
                    <p>Total Sessions</p>
                </div>
                <div class="stat-card">
                    <h3 id="stat-mood-logs">0</h3>
                    <p>Mood Logs</p>
                </div>
                <div class="stat-card">
                    <h3 id="stat-journals">0</h3>
                    <p>Journal Entries</p>
                </div>
            </div>
            
        
            <div class="tabs-container">
                <div class="tabs-header">
                    <button class="tab-button active" onclick="switchTab('users')">Users</button>
                    <button class="tab-button" onclick="switchTab('mood-logs')">Mood Logs</button>
                    <button class="tab-button" onclick="switchTab('sessions')">Sessions & Bookings</button>
                </div>
                
                <div id="users-tab" class="tab-content active">
                    <h2 style="margin-bottom: 20px;">All Registered Users</h2>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Specialty</th>
                                <th>Date Joined</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody id="users-table-body">
                        </tbody>
                    </table>
                </div>
                
                <div id="mood-logs-tab" class="tab-content">
                    <h2 style="margin-bottom: 20px;">All Mood Logs</h2>
                    
                    <div class="user-summary-section">
                        <label for="user-select-summary">View Summary for User:</label>
                        <select id="user-select-summary" onchange="loadUserMoodSummary(this.value)">
                            <option value="">-- Select a User --</option>
                        </select>
                        
                        <div id="user-mood-summary" style="display: none; margin-top: 20px;">
                            <div class="period-toggle" style="margin-bottom: 20px;">
                                <button class="period-btn active" onclick="switchUserPeriod('week')">This Week</button>
                                <button class="period-btn" onclick="switchUserPeriod('month')">This Month</button>
                            </div>
                            
                            <div class="summary-cards-grid">
                                <div class="summary-card-small">
                                    <h4 id="user-total-logs">0</h4>
                                    <p>Total Logs</p>
                                </div>
                                <div class="summary-card-small">
                                    <h4 id="user-avg-intensity">0</h4>
                                    <p>Avg Intensity</p>
                                </div>
                                <div class="summary-card-small">
                                    <h4 id="user-most-common">-</h4>
                                    <p>Most Common</p>
                                </div>
                                <div class="summary-card-small">
                                    <h4 id="user-positive-pct">0%</h4>
                                    <p>Positive Moods</p>
                                </div>
                            </div>
                            
                            <div class="mood-distribution-admin">
                                <h3>Mood Distribution</h3>
                                <div id="user-mood-distribution"></div>
                            </div>
                        </div>
                    </div>
                    
                    <hr style="margin: 30px 0; border: none; border-top: 2px solid #e5e7eb;">
                    
                    <h3>All Mood Logs</h3>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>User</th>
                                <th>Email</th>
                                <th>Mood</th>
                                <th>Intensity</th>
                                <th>Date</th>
                                <th>Notes</th>
                            </tr>
                        </thead>
                        <tbody id="mood-logs-table-body">
                        </tbody>
                    </table>
                </div>
                
                <div id="sessions-tab" class="tab-content">
                    <h2 style="margin-bottom: 20px;">All Therapy Sessions</h2>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Patient</th>
                                <th>Therapist</th>
                                <th>Specialization</th>
                                <th>Scheduled Date</th>
                                <th>Duration</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody id="sessions-table-body">
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        const currentUser = JSON.parse(localStorage.getItem('currentUser') || 'null');
        
        if (!currentUser || currentUser.role !== 'admin') {
            alert('Unauthorized access. Admin only.');
            window.location.href = 'index.html';
        }
        
        document.getElementById('welcome-text').textContent = `Welcome, ${currentUser.name}`;
        
        function switchTab(tabName) {
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            
            document.querySelectorAll('.tab-button').forEach(btn => {
                btn.classList.remove('active');
            });
            
            document.getElementById(tabName + '-tab').classList.add('active');
            
            event.target.classList.add('active');
        }
        
        let selectedUserId = null;
        let selectedUserPeriod = 'week';
        
        function switchUserPeriod(period) {
            selectedUserPeriod = period;
            
            document.querySelectorAll('#user-mood-summary .period-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            event.target.classList.add('active');
            
            if (selectedUserId) {
                loadUserMoodSummary(selectedUserId);
            }
        }
        
        async function loadUserMoodSummary(userId) {
            if (!userId) {
                document.getElementById('user-mood-summary').style.display = 'none';
                return;
            }
            
            selectedUserId = userId;
            document.getElementById('user-mood-summary').style.display = 'block';
            
            try {
                const response = await fetch(`api/mood_summary.php?user_id=${userId}&period=${selectedUserPeriod}`);
                const result = await response.json();
                
                if (result.success) {
    
                    document.getElementById('user-total-logs').textContent = result.summary.total_logs;
                    document.getElementById('user-avg-intensity').textContent = result.summary.avg_intensity;
                    document.getElementById('user-most-common').textContent = result.summary.most_common_mood || '-';
                    document.getElementById('user-positive-pct').textContent = result.summary.positive_percentage + '%';
                    
        
                    const distDiv = document.getElementById('user-mood-distribution');
                    distDiv.innerHTML = '';
                    
                    if (result.mood_distribution.length === 0) {
                        distDiv.innerHTML = '<p style="text-align: center; color: #999;">No mood data for this period</p>';
                        return;
                    }
                    
                    const moodEmojis = {
                        'HAPPY': '😊', 'SAD': '😢', 'ANXIOUS': '😰', 'STRESSED': '😫',
                        'CALM': '😌', 'ANGRY': '😠', 'TIRED': '😴', 'EXCITED': '🤩'
                    };
                    
                    result.mood_distribution.forEach(item => {
                        const bar = document.createElement('div');
                        bar.style.marginBottom = '15px';
                        bar.innerHTML = `
                            <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                                <span>${moodEmojis[item.mood]} ${item.mood}</span>
                                <span><strong>${item.count}</strong> (${item.percentage}%)</span>
                            </div>
                            <div style="background: #e5e7eb; height: 20px; border-radius: 10px; overflow: hidden;">
                                <div style="width: ${item.percentage}%; height: 100%; background: linear-gradient(90deg, #6366f1, #8b5cf6);"></div>
                            </div>
                        `;
                        distDiv.appendChild(bar);
                    });
                }
            } catch (error) {
                console.error('Error loading user mood summary:', error);
            }
        }
        
        
        async function loadDashboardData() {
            try {
                const response = await fetch(`api/admin_dashboard.php?admin_id=${currentUser.id}`);
                const result = await response.json();
                
                if (result.success) {
        
                    document.getElementById('stat-users').textContent = result.data.statistics.total_users;
                    document.getElementById('stat-patients').textContent = result.data.statistics.total_patients;
                    document.getElementById('stat-therapists').textContent = result.data.statistics.total_therapists;
                    document.getElementById('stat-sessions').textContent = result.data.statistics.total_sessions;
                    document.getElementById('stat-mood-logs').textContent = result.data.statistics.total_mood_logs;
                    document.getElementById('stat-journals').textContent = result.data.statistics.total_journals;
                    
                    populateUsersTable(result.data.users);
                    
                    populateMoodLogsTable(result.data.mood_logs);
                    
                    populateSessionsTable(result.data.sessions);
                    
                    document.getElementById('loading').style.display = 'none';
                    document.getElementById('dashboard-content').style.display = 'block';
                } else {
                    alert('Failed to load dashboard data: ' + result.message);
                }
            } catch (error) {
                console.error('Error loading dashboard:', error);
                alert('Error loading dashboard data');
            }
        }
        
        function populateUsersTable(users) {
            const tbody = document.getElementById('users-table-body');
            tbody.innerHTML = '';
            
            const userSelect = document.getElementById('user-select-summary');
            userSelect.innerHTML = '<option value="">-- Select a User --</option>';
            
            if (users.length === 0) {
                tbody.innerHTML = '<tr><td colspan="7" class="no-data">No users found</td></tr>';
                return;
            }
            
            users.forEach(user => {
                const row = document.createElement('tr');
                const roleClass = `role-${user.role}`;
                const statusClass = user.is_active === 'Y' ? 'status-active' : 'status-inactive';
                const statusText = user.is_active === 'Y' ? 'Active' : 'Inactive';
                const specialty = user.specialty || '-';
                
                row.innerHTML = `
                    <td>${user.id}</td>
                    <td>${user.name}</td>
                    <td>${user.email}</td>
                    <td><span class="role-badge ${roleClass}">${user.role}</span></td>
                    <td>${specialty}</td>
                    <td>${new Date(user.date_created).toLocaleDateString()}</td>
                    <td class="${statusClass}">${statusText}</td>
                `;
                
                tbody.appendChild(row);
                
                if (user.role === 'PATIENT') {
                    const option = document.createElement('option');
                    option.value = user.id;
                    option.textContent = `${user.name} (${user.email})`;
                    userSelect.appendChild(option);
                }
            });
        }
        
        function populateMoodLogsTable(moodLogs) {
            const tbody = document.getElementById('mood-logs-table-body');
            tbody.innerHTML = '';
            
            if (moodLogs.length === 0) {
                tbody.innerHTML = '<tr><td colspan="7" class="no-data">No mood logs found</td></tr>';
                return;
            }
            
            moodLogs.forEach(log => {
                const row = document.createElement('tr');
                const moodClass = `mood-${log.mood}`;
                const intensityPercent = (log.intensity / 10) * 100;
                const notes = log.notes || '-';
                
                row.innerHTML = `
                    <td>${log.id}</td>
                    <td>${log.user_name}</td>
                    <td>${log.user_email}</td>
                    <td><span class="mood-badge ${moodClass}">${log.mood}</span></td>
                    <td>
                        <div class="intensity-bar">
                            <div class="intensity-fill" style="width: ${intensityPercent}%"></div>
                        </div>
                        ${log.intensity}/10
                    </td>
                    <td>${new Date(log.log_date).toLocaleDateString()}</td>
                    <td style="max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="${notes}">${notes}</td>
                `;
                
                tbody.appendChild(row);
            });
        }
        
        function populateSessionsTable(sessions) {
            const tbody = document.getElementById('sessions-table-body');
            tbody.innerHTML = '';
            
            if (sessions.length === 0) {
                tbody.innerHTML = '<tr><td colspan="7" class="no-data">No sessions found</td></tr>';
                return;
            }
            
            sessions.forEach(session => {
                const row = document.createElement('tr');
                const statusClass = `status-${session.status}`;
                const scheduledDate = new Date(session.scheduled_date_time);
                
                row.innerHTML = `
                    <td>${session.id}</td>
                    <td>${session.patient_name}<br><small style="color: #999;">${session.patient_email}</small></td>
                    <td>${session.therapist_name}<br><small style="color: #999;">${session.therapist_email}</small></td>
                    <td>${session.specialization || '-'}</td>
                    <td>${scheduledDate.toLocaleString()}</td>
                    <td>${session.duration_minutes} mins</td>
                    <td><span class="status-badge ${statusClass}">${session.status}</span></td>
                `;
                
                tbody.appendChild(row);
            });
        }
        
        function logout() {
            localStorage.removeItem('currentUser');
            window.location.href = 'index.html';
        }
    
        loadDashboardData();
    </script>
</body>
</html>