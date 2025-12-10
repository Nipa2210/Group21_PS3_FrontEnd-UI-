// Lightweight API client for backend endpoints
(function(){
  const base = 'api';

  async function request(path, method='GET', body=null){
    const opts = { method, headers:{}, credentials: 'include' };
    if(body !== null){
      opts.headers['Content-Type'] = 'application/json';
      opts.body = JSON.stringify(body);
    }

    const res = await fetch(path, opts);
    let json = null;
    try{ json = await res.json(); } catch(e){ throw new Error('Invalid JSON response'); }
    if(!json) throw new Error('Empty response');
    return json;
  }

  // Merge into any existing `window.API` to avoid accidental overwrite
  // (helps if a different loader or dev server injects a partial API object).
  window.API = Object.assign(window.API || {}, {
    login: (email, password, role) => request(base + '/auth.php?action=login', 'POST', { email, password, role }),
    logout: () => request(base + '/auth.php?action=logout', 'POST', {}),
    checkSession: () => request(base + '/auth.php?action=check-session', 'GET'),

    // Users
    listUsers: () => request(base + '/users.php?action=list', 'GET'),
    getUsersByRole: (role) => request(base + '/users.php?action=by-role&role=' + encodeURIComponent(role), 'GET'),
    createUser: (payload) => request(base + '/users.php?action=create', 'POST', payload),

    // Courses
    listCourses: () => request(base + '/courses.php?action=list', 'GET'),
    getCourse: (id) => request(base + '/courses.php?action=get&id=' + encodeURIComponent(id), 'GET'),
    getCoursesByInstructor: (id) => request(base + '/courses.php?action=by-instructor&id=' + encodeURIComponent(id), 'GET'),
    getEnrolled: (userId) => request(base + '/courses.php?action=enrolled&id=' + encodeURIComponent(userId), 'GET'),
    enroll: (courseId) => request(base + '/courses.php?action=enroll', 'POST', { course_id: courseId }),
    createCourse: (payload) => request(base + '/courses.php?action=create', 'POST', payload),

    // Submissions
    pendingSubmissions: () => request(base + '/courses.php?action=pending-submissions', 'GET'),
    approveSubmission: (submissionId, notes='') => request(base + '/courses.php?action=approve-submission', 'POST', { submission_id: submissionId, notes }),
    rejectSubmission: (submissionId, notes='') => request(base + '/courses.php?action=reject-submission', 'POST', { submission_id: submissionId, notes }),

    // Analytics
    platformStats: () => request(base + '/analytics.php?action=platform-stats', 'GET'),
    activeLearners: (days=30) => request(base + '/analytics.php?action=active-learners&days=' + encodeURIComponent(days), 'GET'),

    // Registrations
    pendingRegistrations: () => request(base + '/registrations.php?action=pending', 'GET'),
    allRegistrations: () => request(base + '/registrations.php?action=all', 'GET'),
    createRegistration: (courseId, amount=0) => request(base + '/registrations.php?action=create-registration', 'POST', { course_id: courseId, amount }),
    approveRegistration: (registrationId) => request(base + '/registrations.php?action=approve-registration', 'POST', { registration_id: registrationId }),
    recordPayment: (registrationId) => request(base + '/registrations.php?action=record-payment', 'POST', { registration_id: registrationId }),

    // Certificates
    listCertificates: () => request(base + '/certificates.php?action=list', 'GET'),
    listAllCertificates: () => request(base + '/certificates.php?action=all', 'GET'),

    // Helper for errors
    requestRaw: request
  });

  // Small debug marker to help troubleshooting in the browser console
  try{ window.API._clientVersion = window.API._clientVersion || 'api-client-v1'; } catch(e){}
})();
