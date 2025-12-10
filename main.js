// Main frontend wiring: uses existing DOM (index.php) and calls window.API

document.addEventListener('DOMContentLoaded', async function(){
  const loginRole = document.getElementById('loginRole');
  const loginEmail = document.getElementById('loginEmail');
  const loginPassword = document.getElementById('loginPassword');
  const loginBtn = document.getElementById('loginBtn');
  const demoBtn = document.getElementById('demoBtn');
  const tryDemoTopBtn = document.getElementById('tryDemoTopBtn');
  const getStartedBtn = document.getElementById('getStartedBtn');
  const currentRoleEl = document.getElementById('currentRole');
  const logoutBtn = document.getElementById('logoutBtn');
  const tabNav = document.getElementById('tabNav');
  const views = Array.from(document.querySelectorAll('.view'));
  const appShell = document.getElementById('app-shell');
  const loginForm = document.getElementById('loginForm');
  const newCourseBtn = document.getElementById('newCourseBtn');
  const submitCourseBtn = document.getElementById('submitCourseBtn');
  const courseModal = document.getElementById('courseModal');

  let session = null;
  // admin view state
  let adminCurrentUsers = [];
  let adminCurrentFilter = '';

  function showLoginCard(){
    views.forEach(v=>v.style.display='none');
    loginForm.style.display='block';
    currentRoleEl.textContent = 'Not logged in';
    logoutBtn.style.display = 'none';
    tabNav.style.display = 'none';
  }

  async function restoreSession(){
    try{
      const res = await window.API.checkSession();
      if(res && res.success){
        session = res.data;
        updateUIForSession();
        showAppShell();
        await showView(session.role);
      } else {
        showLoginCard();
      }
    } catch(e){
      console.warn('Session restore failed', e);
      showLoginCard();
    }
  }

  function showAppShell(){
    appShell.style.display='block';
    document.getElementById('landingHero').style.display='none';
  }

  function updateUIForSession(){
    if(!session) return showLoginCard();
    currentRoleEl.textContent = (session.name || session.email) + ' · ' + (session.role || '');
    logoutBtn.style.display = 'inline-block';
    loginForm.style.display = 'none';
    tabNav.style.display = 'flex';
  }

  async function showView(role){
    if(!role){ showLoginCard(); return; }
    if(!session || session.role !== role){ alert('Access denied: log in with role "' + role + '" first.'); return; }

    Array.from(document.querySelectorAll('.tab-pill')).forEach(it => it.classList.toggle('active', it.dataset.role === role));
    views.forEach(v => v.style.display = (v.id === role ? 'block' : 'none'));
    updateUIForSession();
    await renderRoleData(role);
    window.scrollTo({top: appShell.offsetTop - 20, behavior:'smooth'});
  }

  // Attach tab role clicks
  Array.from(document.querySelectorAll('.tab-pill')).forEach(item=>{
    item.addEventListener('click', async () => {
      const selectedRole = item.dataset.role;
      if(!session){ alert('Please login first.'); return; }
      if(session.role !== selectedRole){ alert('Permission denied — you are logged in as ' + session.role); return; }
      await showView(selectedRole);
    });
  });

  // Login
  loginBtn.addEventListener('click', async function(){
    const role = loginRole.value;
    const email = (loginEmail.value || '').trim();
    const pwd = loginPassword.value || '';
    if(!role){ alert('Please select a role.'); return; }
    try{
      const res = await window.API.login(email, pwd, role);
      if(res && res.success){
        session = res.data;
        showAppShell();
        updateUIForSession();
        await showView(session.role);
      } else {
        alert(res && res.message ? res.message : 'Login failed');
      }
    } catch(err){ alert('Login error: ' + err.message); }
  });

  // Demo quickfill
  demoBtn.addEventListener('click', function(){ loginRole.value = 'student'; loginEmail.value='student@demo'; loginPassword.value='demo'; loginBtn.click(); });
  tryDemoTopBtn.addEventListener('click', function(){ loginRole.value='student'; loginEmail.value='student@demo'; loginPassword.value='demo'; loginBtn.click(); });
  getStartedBtn.addEventListener('click', ()=>{ appShell.scrollIntoView({behavior:'smooth'}); loginForm.scrollIntoView({behavior:'smooth'}); });

  logoutBtn.addEventListener('click', async function(){ if(!confirm('Logout?')) return; try{ await window.API.logout(); session = null; showLoginCard(); alert('Logged out'); } catch(e){ alert('Logout failed'); }});

  // New Course Modal
  if(newCourseBtn){
    newCourseBtn.addEventListener('click', ()=>{ courseModal.classList.add('show'); });
  }
  
  if(submitCourseBtn){
    submitCourseBtn.addEventListener('click', async ()=>{
      const title = document.getElementById('courseTitle').value || '';
      const category = document.getElementById('courseCategory').value || '';
      const desc = document.getElementById('courseDescription').value || '';
      const price = parseFloat(document.getElementById('coursePrice').value) || 0;

      if(!title || !category) { alert('Title and category required'); return; }
      
      try{
        const res = await window.API.createCourse({
          title, 
          category, 
          description: desc,
          price,
          instructor_id: session.user_id,
          status: 'Draft'
        });
        
        if(res && res.success){
          alert('Course created successfully!');
          courseModal.classList.remove('show');
          document.getElementById('courseTitle').value = '';
          document.getElementById('courseCategory').value = '';
          document.getElementById('courseDescription').value = '';
          document.getElementById('coursePrice').value = '';
          await renderRoleData('instructor');
        } else {
          alert(res && res.message ? res.message : 'Failed to create course');
        }
      } catch(e){ alert('Error: ' + e.message); }
    });
  }

  // View Students modal wiring
  const viewStudentsBtn = document.getElementById('viewStudentsBtn');
  const studentsModal = document.getElementById('studentsModal');
  const studentsTableBody = document.querySelector('#studentsTable tbody');
  const studentsModalClose = document.getElementById('studentsModalClose');
  const studentsSearch = document.getElementById('studentsSearch');

  async function fetchAndShowStudents(){
    if(!session) { alert('Please login as Admin or Instructor to view students'); return; }
    if(!(session.role === 'admin' || session.role === 'instructor')){ alert('Only Admins and Instructors can view the student directory'); return; }
    try{
      const res = await window.API.getUsersByRole('student');
      const list = (res && res.success && Array.isArray(res.data)) ? res.data : [];
      studentsTableBody.innerHTML = list.map(s => `<tr><td>${s.name||''}</td><td>${s.email||''}</td><td>${s.role||''}</td><td>${s.created_at||s.registered_at||''}</td></tr>`).join('');
      studentsModal.classList.add('show');
      // simple client-side search hook
      studentsSearch.value = '';
    } catch(e){ alert('Failed to load students: ' + e.message); }
  }

  if(viewStudentsBtn){
    viewStudentsBtn.addEventListener('click', fetchAndShowStudents);
  }

  // Admin role filter + export
  const adminRoleFilter = document.getElementById('adminRoleFilter');
  const exportUsersBtn = document.getElementById('exportUsersBtn');
  function usersToCSV(rows){
    if(!Array.isArray(rows)) return '';
    const cols = ['id','name','role','email','phone','created_at','is_active'];
    const header = cols.join(',') + '\n';
    const lines = rows.map(r => cols.map(c => {
      let v = r[c]; if(typeof v === 'undefined' || v === null) v = '';
      // escape quotes
      v = String(v).replace(/"/g,'""');
      if(v.indexOf(',') >= 0 || v.indexOf('"') >= 0) v = '"' + v + '"';
      return v;
    }).join(',')).join('\n');
    return header + lines;
  }

  if(adminRoleFilter){
    adminRoleFilter.addEventListener('change', async function(){
      adminCurrentFilter = this.value || '';
      try{
        if(adminCurrentFilter){
          const res = await window.API.getUsersByRole(adminCurrentFilter);
          adminCurrentUsers = (res && res.success && Array.isArray(res.data)) ? res.data : [];
        } else {
          const res = await window.API.listUsers();
          adminCurrentUsers = (res && res.success && Array.isArray(res.data)) ? res.data : [];
        }
        // re-render admin table manually
        document.querySelector('#adminUsersTable tbody').innerHTML = adminCurrentUsers.map(u=>{
          const phone = u.phone || '';
          const created = u.created_at || '';
          const active = (typeof u.is_active !== 'undefined') ? (u.is_active ? 'Yes' : 'No') : '';
          return `<tr><td>${u.name||''}</td><td>${u.role||''}</td><td>${u.email||''}</td><td>${phone}</td><td>${created}</td><td>${active}</td><td><button class="action-btn" data-action="view" data-id="${u.id}">View</button> <button class="action-btn action-reject" data-action="delete" data-id="${u.id}">Delete</button></td></tr>`;
        }).join('');
      } catch(e){ alert('Failed to filter users: ' + e.message); }
    });
  }

  if(exportUsersBtn){
    exportUsersBtn.addEventListener('click', function(){
      const rows = adminCurrentUsers && adminCurrentUsers.length ? adminCurrentUsers : [];
      if(rows.length === 0){ alert('No users to export. Try selecting All roles first.'); return; }
      const csv = usersToCSV(rows);
      const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
      const url = URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = 'users_export.csv';
      document.body.appendChild(a);
      a.click();
      a.remove();
      URL.revokeObjectURL(url);
    });
  }

  if(studentsModalClose){
    studentsModalClose.addEventListener('click', ()=> studentsModal.classList.remove('show'));
  }

  if(studentsSearch){
    studentsSearch.addEventListener('keyup', function(){
      const q = (this.value || '').toLowerCase();
      Array.from(studentsTableBody.querySelectorAll('tr')).forEach(row=>{
        row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
      });
    });
  }

  // Add User modal wiring
  const addUserBtn = document.getElementById('addUserBtn');
  const addUserModal = document.getElementById('addUserModal');
  const addUserModalClose = document.getElementById('addUserModalClose');
  const submitAddUserBtn = document.getElementById('submitAddUserBtn');
  const cancelAddUserBtn = document.getElementById('cancelAddUserBtn');

  // User Details modal elements
  const userDetailsModal = document.getElementById('userDetailsModal');
  const userDetailsBody = document.getElementById('userDetailsBody');
  const userDetailsModalClose = document.getElementById('userDetailsModalClose');
  const userDetailsCloseBtn = document.getElementById('userDetailsCloseBtn');

  if(addUserBtn){
    addUserBtn.addEventListener('click', ()=>{
      if(!session || session.role !== 'admin'){ alert('Only admins can create users'); return; }
      addUserModal.classList.add('show');
    });
  }

  if(addUserModalClose) addUserModalClose.addEventListener('click', ()=> addUserModal.classList.remove('show'));
  if(cancelAddUserBtn) cancelAddUserBtn.addEventListener('click', ()=> addUserModal.classList.remove('show'));

  if(userDetailsModalClose) userDetailsModalClose.addEventListener('click', ()=> userDetailsModal.classList.remove('show'));
  if(userDetailsCloseBtn) userDetailsCloseBtn.addEventListener('click', ()=> userDetailsModal.classList.remove('show'));

  if(submitAddUserBtn){
    submitAddUserBtn.addEventListener('click', async ()=>{
      const name = (document.getElementById('addUserName').value || '').trim();
      const email = (document.getElementById('addUserEmail').value || '').trim();
      const password = (document.getElementById('addUserPassword').value || '').trim();
      const role = (document.getElementById('addUserRole').value || '').trim();

      if(!name || !email || !password || !role){ alert('All fields are required'); return; }

      try{
        const res = await window.API.createUser({ name, email, password, role });
        console.log('createUser response:', res);
        if(res && res.success){
          alert('User created successfully');
          addUserModal.classList.remove('show');
          // refresh admin user table if visible
          if(session && session.role === 'admin') await renderRoleData('admin');
        } else {
          // show full response to help debugging
          alert('Create user failed: ' + (res && res.message ? res.message : 'Unknown error') + '\n\nFull response logged to console');
          try{ console.log('Full createUser response:', JSON.stringify(res, null, 2)); } catch(e){}
        }
      } catch(e){ console.error('createUser error', e); alert('Error: ' + e.message); }
    });
  }

  // Render role data (calls backend where possible)
  async function renderRoleData(role){
    try{
      if(role === 'student'){
        let userId = (session && session.user_id) ? parseInt(session.user_id) : null;
        // Resolve demo fallback
        if(!userId && session && session.email){
          try{
            const studentsListRes = await window.API.getUsersByRole('student');
            const studentsList = (studentsListRes && studentsListRes.success && Array.isArray(studentsListRes.data)) ? studentsListRes.data : [];
            const match = studentsList.find(u => u.email === session.email);
            if(match && match.id){ userId = parseInt(match.id); session.user_id = userId; }
          } catch(e){ console.warn('Could not resolve numeric user id from email', e); }
        }

        // Fetch all courses and enrolled courses
        const allCoursesRes = await window.API.listCourses();
        const allCourses = (allCoursesRes && allCoursesRes.success && Array.isArray(allCoursesRes.data)) ? allCoursesRes.data : [];
        let enrolledItems = [];
        if(userId){
          const enrolledRes = await window.API.getEnrolled(userId);
          enrolledItems = (enrolledRes && enrolledRes.success && Array.isArray(enrolledRes.data)) ? enrolledRes.data : [];
        }
        document.getElementById('stuCoursesCount').textContent = enrolledItems.length;
        document.getElementById('stuCertificates').textContent = enrolledItems.filter(i=>i.status==='Completed').length;

        // Build set of enrolled course IDs
        const enrolledIds = new Set(enrolledItems.map(e => String(e.id)));

        // Render enrolled courses table
        const tbody = document.querySelector('#stuTable tbody');
        if(enrolledItems.length > 0){
          tbody.innerHTML = enrolledItems.map(s => `<tr><td>${s.title}</td><td>${s.instructor_name||s.instructor||''}</td><td>${s.status||''}</td><td>${s.progress||0}%</td></tr>`).join('');
        } else {
          tbody.innerHTML = `<tr><td colspan="4">No enrollments yet.</td></tr>`;
        }

        // Render available courses with enroll buttons (only for unenrolled)
        const availableCourses = allCourses.filter(c => !enrolledIds.has(String(c.id)));
        let enrollTable = document.getElementById('availableCoursesTable');
        if(!enrollTable){
          enrollTable = document.createElement('table');
          enrollTable.id = 'availableCoursesTable';
          enrollTable.className = 'table';
          enrollTable.innerHTML = `<thead><tr><th>Course</th><th>Instructor</th><th>Category</th><th>Action</th></tr></thead><tbody></tbody>`;
          tbody.parentElement.parentElement.appendChild(enrollTable);
        }
        const enrollTbody = enrollTable.querySelector('tbody');
        if(availableCourses.length === 0){
          enrollTbody.innerHTML = `<tr><td colspan="4">All available courses are already enrolled.</td></tr>`;
        } else {
          enrollTbody.innerHTML = availableCourses.map(c => `<tr><td>${c.title||''}</td><td>${c.instructor_name||c.instructor||''}</td><td>${c.category||''}</td><td><button class="action-btn action-enroll" data-action="enroll-course" data-id="${c.id}">Enroll</button></td></tr>`).join('');
        }

        // Render certificate list for the student (below the courses table)
        try{
          const certAreaId = 'stuCertificatesList';
          const parentCard = document.querySelector('#stuTable') && document.querySelector('#stuTable').closest('.card');
          if(parentCard){
            let certArea = document.getElementById(certAreaId);
            if(!certArea){
              certArea = document.createElement('div');
              certArea.id = certAreaId;
              certArea.style.marginTop = '12px';
              certArea.innerHTML = `<div style="font-weight:700; margin-bottom:8px;">🎖️ Your Certificates</div><div id="stuCertListContent">Loading...</div>`;
              parentCard.appendChild(certArea);
            } else {
              document.getElementById('stuCertListContent').textContent = 'Loading...';
            }

            const certRes = await window.API.listCertificates().catch(()=>({success:false}));
            const certs = (certRes && certRes.success && Array.isArray(certRes.data)) ? certRes.data : [];
            document.getElementById('stuCertificates').textContent = certs.length;

            const content = document.getElementById('stuCertListContent');
            if(!content) return;
            if(certs.length === 0){
              content.innerHTML = '<div style="color:#64748b">No certificates yet. Complete courses to earn certificates.</div>';
            } else {
              content.innerHTML = `<div style="overflow:auto;"><table style="width:100%;border-collapse:collapse;"><thead><tr style="background:#f8fafc;color:#374151;font-weight:700;"><th style="padding:8px">Course</th><th style="padding:8px">Certificate#</th><th style="padding:8px">Issued</th><th style="padding:8px">Action</th></tr></thead><tbody>${certs.map(c=>`<tr><td style="padding:8px">${c.course_title||''}</td><td style="padding:8px">${c.certificate_number||c.certificate||c.id||''}</td><td style="padding:8px">${c.created_at||c.issued_at||''}</td><td style="padding:8px"><a href="api/certificates.php?action=list&download=${encodeURIComponent(c.id)}" target="_blank" class="action-btn" style="background:#eef2ff;color:#4f46e5;">View</a></td></tr>`).join('')}</tbody></table></div>`;
            }
          }
        } catch(e){ console.warn('Failed to load certificates', e); }
      }

      if(role === 'instructor'){
        const userId = parseInt(session.user_id) || null;
        if(!userId) { console.warn('No user_id for instructor'); return; }
        const instrRes = await window.API.getCoursesByInstructor(userId);
        const courses = (instrRes && instrRes.success && Array.isArray(instrRes.data)) ? instrRes.data : [];
        document.getElementById('instrActiveCourses').textContent = courses.length;
        const totalStudents = courses.reduce((acc,c)=>acc + (parseInt(c.enrolled)||0), 0);
        document.getElementById('instrTotalStudents').textContent = totalStudents;
        document.querySelector('#instrTable tbody').innerHTML = courses.map(c => `<tr><td>${c.title}</td><td>${c.category||''}</td><td>${c.enrolled||0}</td><td><button class="action-btn action-edit" data-action="edit" data-id="${c.id}">Edit</button> <button class="action-btn action-reject" data-action="delete" data-id="${c.id}">Delete</button></td></tr>`).join('');
      }

      if(role === 'admin'){
        const usersRes = await window.API.listUsers();
        const users = (usersRes && usersRes.success && Array.isArray(usersRes.data)) ? usersRes.data : [];
        document.getElementById('totalUsers').textContent = users.length;
        const coursesRes = await window.API.listCourses();
        const courses = (coursesRes && coursesRes.success && Array.isArray(coursesRes.data)) ? coursesRes.data : [];
        document.getElementById('totalCourses').textContent = courses.length;
        // Render richer admin table with more user info and actions
        document.querySelector('#adminUsersTable tbody').innerHTML = users.map(u=>{
          const phone = u.phone || '';
          const created = u.created_at || '';
          const active = (typeof u.is_active !== 'undefined') ? (u.is_active ? 'Yes' : 'No') : '';
          return `<tr><td>${u.name||''}</td><td>${u.role||''}</td><td>${u.email||''}</td><td>${phone}</td><td>${created}</td><td>${active}</td><td><button class="action-btn" data-action="view" data-id="${u.id}">View</button> <button class="action-btn action-reject" data-action="delete" data-id="${u.id}">Delete</button></td></tr>`;
        }).join('');
      }

      if(role === 'course_team'){
        const subRes = await window.API.pendingSubmissions();
        const items = (subRes && subRes.success && Array.isArray(subRes.data)) ? subRes.data : [];
        document.getElementById('pendingCount').textContent = items.length;
        document.getElementById('underReviewCount').textContent = items.filter(s=>s.status==='Under Review').length;
        document.querySelector('#courseTeamTable tbody').innerHTML = items.map(p => `<tr><td>${p.course_title||''}</td><td>${p.submitted_by_name||''}</td><td>${p.submitted_at||''}</td><td><button class="action-btn action-approve" data-action="approve" data-id="${p.id}">Approve</button> <button class="action-btn action-reject" data-action="reject" data-id="${p.id}">Reject</button></td></tr>`).join('');
      }

      if(role === 'dept_head'){
        const statsRes = await window.API.platformStats();
        const stats = (statsRes && statsRes.success) ? statsRes.data : {};
        document.getElementById('facultyCount').textContent = stats.total_users || '—';
        document.getElementById('activeProjects').textContent = stats.total_courses || 0;
      }

      if(role === 'data_analyst'){
        const statsRes = await window.API.platformStats();
        if(statsRes && statsRes.success){
          document.getElementById('activeLearners').textContent = statsRes.data.total_enrollments || 0;
          const rate = statsRes.data.completion_rate ? statsRes.data.completion_rate.toString().substring(0, 5) : '0';
          document.getElementById('avgCompletion').textContent = rate + '%';
        }
      }

      if(role === 'office_manager'){
        const regsRes = await window.API.pendingRegistrations();
        const items = (regsRes && regsRes.success && Array.isArray(regsRes.data)) ? regsRes.data : [];
        document.getElementById('pendingRegs').textContent = items.length;
        const totalAmount = items.reduce((a,b)=>a + (parseFloat(b.amount)||0),0);
        document.getElementById('paymentsReceived').textContent = '$' + totalAmount.toFixed(2);
        document.getElementById('docsQueue').textContent = items.length;
        document.querySelector('#regTable tbody').innerHTML = items.map(r => `<tr><td>${r.student_name||''}</td><td>${r.course_title||''}</td><td>${r.registered_at||''}</td><td><button class="action-btn action-approve" data-action="approve-reg" data-id="${r.id}">Approve</button></td></tr>`).join('');
      }
    } catch(e){ console.error('Render error', e); }
  }

  /* ---------- Charts (Chart.js) ---------- */
  const charts = {};

  function getCtx(id){
    const el = document.getElementById(id);
    if(!el) return null;
    try{ return el.getContext('2d'); } catch(e){ return null; }
  }

  function initChartsForRole(role, data){
    try{
      if(role === 'student'){
        const ctx = getCtx('stuChart'); if(!ctx) return;
        const counts = data && data.length ? [data.filter(d=>d.status==='Completed').length, data.filter(d=>d.status==='Active').length, data.filter(d=>d.status!=='Active'&&d.status!=='Completed').length] : [35,50,15];
        if(!charts.stu){ charts.stu = new Chart(ctx, { type:'doughnut', data:{ labels:['Completed','In Progress','Other'], datasets:[{ data:counts, backgroundColor:['#10b981','#06b6d4','#c7d2fe'] }] }, options:{ maintainAspectRatio:false, plugins:{legend:{position:'bottom'}} } }); }
        else { charts.stu.data.datasets[0].data = counts; charts.stu.update(); }
      }

      if(role === 'instructor'){
        const ctx = getCtx('instrChart'); if(!ctx) return;
        const labels = (data && data.length) ? data.map(c=>c.title) : ['Course A','Course B','Course C'];
        const values = (data && data.length) ? data.map(c=>parseInt(c.enrolled)||0) : [120,80,65];
        if(!charts.instr){ charts.instr = new Chart(ctx, { type:'bar', data:{ labels, datasets:[{ label:'Enrolled', data: values, backgroundColor:'#7c3aed' }]}, options:{ maintainAspectRatio:false } }); }
        else { charts.instr.data.labels = labels; charts.instr.data.datasets[0].data = values; charts.instr.update(); }
      }

      if(role === 'admin'){
        const ctx = getCtx('adminChart'); if(!ctx) return;
        const labels = ['Jan','Feb','Mar','Apr','May'];
        const values = (data && data.usersOverTime) ? data.usersOverTime : [150,180,220,290,350];
        if(!charts.admin){ charts.admin = new Chart(ctx, { type:'line', data:{ labels, datasets:[{ label:'Users', data: values, borderColor:'#06b6d4', tension:0.3 }] }, options:{ maintainAspectRatio:false } }); }
        else { charts.admin.data.datasets[0].data = values; charts.admin.update(); }
      }

      if(role === 'course_team'){
        const ctx = getCtx('courseCatChart'); if(!ctx) return;
        const counts = {};
        if(data && data.length){ data.forEach(c=> counts[c.category] = (counts[c.category]||0)+1); }
        const labels = Object.keys(counts).length ? Object.keys(counts) : ['AI','DB','IT'];
        const values = Object.keys(counts).length ? Object.values(counts) : [5,3,2];
        if(!charts.courseCat){ charts.courseCat = new Chart(ctx, { type:'pie', data:{ labels, datasets:[{ data:values, backgroundColor:['#7c3aed','#06b6d4','#f59e0b','#ef4444'] }] }, options:{ maintainAspectRatio:false }}); }
        else { charts.courseCat.data.labels = labels; charts.courseCat.data.datasets[0].data = values; charts.courseCat.update(); }
      }

      if(role === 'dept_head'){
        const ctx = getCtx('budgetChart'); if(!ctx) return;
        const labels = ['Q1','Q2','Q3','Q4'];
        const budget = [50,60,55,70];
        const spend = [45,50,40,65];
        if(!charts.budget){ charts.budget = new Chart(ctx, { type:'bar', data:{ labels, datasets:[{ label:'Budget', data: budget, backgroundColor:'#7c3aed' },{ label:'Spend', data: spend, backgroundColor:'#06b6d4' }] }, options:{ maintainAspectRatio:false } }); }
        else { charts.budget.update(); }
      }

      if(role === 'data_analyst'){
        const ctx = getCtx('engageChart'); if(!ctx) return;
        const labels = ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'];
        const values = (data && data.engagement7d) ? data.engagement7d : [300,420,380,450,500,470,430];
        if(!charts.engage){ charts.engage = new Chart(ctx, { type:'line', data:{ labels, datasets:[{ label:'Active', data: values, borderColor:'#7c3aed', fill:false }] }, options:{ maintainAspectRatio:false } }); }
        else { charts.engage.data.datasets[0].data = values; charts.engage.update(); }
      }

    } catch(e){ console.warn('Chart init error', e); }
  }

  // Hook into renderRoleData to initialize charts after render completes
  const originalRender = renderRoleData;
  renderRoleData = async function(role){
    await originalRender(role);
    try{
      if(role === 'student'){
        const userId = parseInt(session.user_id) || null;
        if(userId){
          const enrolledRes = await window.API.getEnrolled(userId);
          const items = (enrolledRes && enrolledRes.success && Array.isArray(enrolledRes.data)) ? enrolledRes.data : [];
          initChartsForRole('student', items);
        }
      }
      if(role === 'instructor'){
        const userId = parseInt(session.user_id) || null;
        if(userId){
          const instrRes = await window.API.getCoursesByInstructor(userId);
          const courses = (instrRes && instrRes.success && Array.isArray(instrRes.data)) ? instrRes.data : [];
          initChartsForRole('instructor', courses);
        }
      }
      if(role === 'admin'){
        const statsRes = await window.API.platformStats().catch(()=>null);
        const payload = (statsRes && statsRes.success) ? statsRes.data : { usersOverTime:[150,180,220,290,350] };
        initChartsForRole('admin', payload);
      }
      if(role === 'course_team'){
        const coursesRes = await window.API.listCourses().catch(()=>null);
        const courses = (coursesRes && coursesRes.success) ? coursesRes.data : [];
        initChartsForRole('course_team', courses);
      }
      if(role === 'dept_head'){
        initChartsForRole('dept_head', null);
      }
      if(role === 'data_analyst'){
        const analyticsRes = await window.API.platformStats().catch(()=>null);
        const payload = (analyticsRes && analyticsRes.success) ? { engagement7d: [300,420,380,450,500,470,430] } : null;
        initChartsForRole('data_analyst', payload);
      }
    } catch(e){ console.warn('Chart data fetch error', e); }
  };

  // Generic table actions
  document.addEventListener('click', async function(e){
    const t = e.target;
    if(!t || !t.classList.contains('action-btn')) return;
    
    const action = t.dataset.action;
    if(!action) return;

    if(action === 'approve'){
      const id = t.dataset.id; 
      if(!id) return;
      try{ 
        const res = await window.API.approveSubmission(id); 
        if(res && res.success){ 
          t.textContent='✓ Approved'; 
          t.disabled=true; 
          t.style.opacity=0.5; 
        } else alert(res && res.message ? res.message : 'Approval failed'); 
      } catch(e){ alert('Error: ' + e.message); }
    }
    
    if(action === 'reject'){
      const id = t.dataset.id; 
      if(!id) return;
      try{ 
        const res = await window.API.rejectSubmission(id); 
        if(res && res.success){ 
          t.textContent='✗ Rejected'; 
          t.disabled=true; 
          t.style.opacity=0.5; 
        } else alert(res && res.message ? res.message : 'Rejection failed'); 
      } catch(e){ alert('Error: ' + e.message); }
    }

    if(action === 'view'){
      const id = t.dataset.id;
      if(!id) return;
      try{
        const res = await window.API.requestRaw('api/users.php?action=get&id=' + encodeURIComponent(id), 'GET');
        if(res && res.success){
          const u = res.data;
          if(userDetailsBody){
            userDetailsBody.innerHTML = `
              <div style="line-height:1.6">
                <strong>Name:</strong> ${u.name||''}<br/>
                <strong>Email:</strong> ${u.email||''}<br/>
                <strong>Role:</strong> ${u.role||''}<br/>
                <strong>Phone:</strong> ${u.phone||''}<br/>
                <strong>Address:</strong> ${u.address||''}<br/>
                <strong>Created:</strong> ${u.created_at||''}<br/>
                <strong>Active:</strong> ${typeof u.is_active !== 'undefined' ? (u.is_active ? 'Yes' : 'No') : ''}
              </div>
            `;
          }
          if(userDetailsModal) userDetailsModal.classList.add('show');
        } else {
          alert(res && res.message ? res.message : 'Failed to load user details');
        }
      } catch(e){ alert('Error: ' + e.message); }
    }

    if(action === 'approve-reg'){
      const id = t.dataset.id; 
      if(!id) return;
      try{ 
        const res = await window.API.approveRegistration(id); 
        if(res && res.success){ 
          t.textContent='✓ Approved'; 
          t.disabled=true; 
          t.style.opacity=0.5; 
        } else alert(res && res.message ? res.message : 'Approval failed'); 
      } catch(e){ alert('Error: ' + e.message); }
    }

    if(action === 'delete'){
      const row = t.closest('tr'); 
      if(row && confirm('Delete this record?')) { 
        row.remove(); 
      }
    }

    if(action === 'enroll-course'){
      const courseId = t.dataset.id;
      if(!courseId) return;
      if(!session){ alert('Please login first'); return; }
      try{
        const res = await window.API.enroll(courseId);
        if(res && res.success){
          alert('Enrolled successfully');
          // Refresh student view
          if(session && session.role === 'student') await renderRoleData('student');
        } else {
          alert(res && res.message ? res.message : 'Enrollment failed');
        }
      } catch(e){ alert('Error: ' + e.message); }
    }

    if(action === 'edit'){
      alert('Edit functionality coming soon');
    }
  });

  // Search functionality
  document.addEventListener('keyup', function(e){
    if(!e.target.classList.contains('search')) return;
    const searchVal = e.target.value.toLowerCase();
    const role = session && session.role ? session.role : null;
    
    if(role === 'student' && e.target.id === 'studentSearch'){
      const rows = document.querySelectorAll('#stuTable tbody tr');
      rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(searchVal) ? '' : 'none';
      });
    }
  });

  // Restore session on page load
  await restoreSession();

});
