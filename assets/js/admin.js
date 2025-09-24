// assets/js/admin.js

document.addEventListener('DOMContentLoaded', () => {
    // Check for admin status first
    fetch('../api/user_status.php')
        .then(res => res.json())
        .then(data => {
            if (!data.loggedIn || !data.user.isAdmin) {
                // If not an admin, redirect away
                window.location.href = 'index.html';
            } else {
                // If admin, load all the dashboard data
                loadStats();
                loadUsers();
                loadPlans();
            }
        })
        .catch(() => {
            window.location.href = 'index.html'; // Redirect on any error
        });

    document.getElementById('logout-btn').addEventListener('click', async () => {
        await fetch('../api/logout.php');
        window.location.href = 'index.html';
    });

    const userSearchInput = document.getElementById('user-search');
    userSearchInput.addEventListener('input', () => loadUsers(userSearchInput.value));
});

function openAdminTab(evt, tabName) {
    const tabContents = document.getElementsByClassName('admin-tab-content');
    for (let i = 0; i < tabContents.length; i++) {
        tabContents[i].style.display = 'none';
    }
    const tabLinks = document.getElementsByClassName('tab-link');
    for (let i = 0; i < tabLinks.length; i++) {
        tabLinks[i].className = tabLinks[i].className.replace(' active', '');
    }
    document.getElementById(tabName).style.display = 'block';
    evt.currentTarget.className += ' active';
}

async function fetchData(action, params = '') {
    try {
        const response = await fetch(`../api/admin_data.php?action=${action}&${params}`);
        if (!response.ok) {
            throw new Error('Failed to fetch data');
        }
        return await response.json();
    } catch (error) {
        console.error(`Error fetching ${action}:`, error);
        return null;
    }
}

async function loadStats() {
    const data = await fetchData('stats');
    if (!data) return;

    document.getElementById('total-revenue').textContent = '$' + data.totalRevenue;
    document.getElementById('monthly-revenue').textContent = '$' + data.monthlyRevenue;
    document.getElementById('total-users').textContent = data.totalUsers;

    // Render Chart.js chart
    const ctx = document.getElementById('revenue-chart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: data.chartData.labels,
            datasets: [{
                label: 'Revenue per Month',
                data: data.chartData.values,
                borderColor: '#bb86fc',
                backgroundColor: 'rgba(187, 134, 252, 0.2)',
                fill: true,
                tension: 0.3
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: { beginAtZero: true }
            }
        }
    });
}

async function loadUsers(searchTerm = '') {
    const data = await fetchData('users', `search=${encodeURIComponent(searchTerm)}`);
    if (!data) return;

    const userListContainer = document.getElementById('user-list');
    userListContainer.innerHTML = ''; // Clear previous list

    if (data.users.length === 0) {
        userListContainer.innerHTML = '<p>No users found.</p>';
        return;
    }

    const table = document.createElement('table');
    table.className = 'w-full text-left';
    table.innerHTML = `
        <thead>
            <tr class="border-b border-gray-600">
                <th class="p-2">ID</th>
                <th class="p-2">Email</th>
                <th class="p-2">Subscription</th>
                <th class="p-2">IP Address</th>
                <th class="p-2">Registered</th>
                <th class="p-2">Actions</th>
            </tr>
        </thead>
        <tbody>
            ${data.users.map(user => `
                <tr class="border-b border-gray-700">
                    <td class="p-2">${user.id}</td>
                    <td class="p-2">${user.email}</td>
                    <td class="p-2">${user.subscription_name}</td>
                    <td class="p-2">${user.ip_address}</td>
                    <td class="p-2">${new Date(user.created_at).toLocaleDateString()}</td>
                    <td class="p-2">
                        <button class="text-blue-400 hover:underline">Edit</button> |
                        <button class="text-red-400 hover:underline">Disable</button>
                    </td>
                </tr>
            `).join('')}
        </tbody>
    `;
    userListContainer.appendChild(table);
}

async function loadPlans() {
    const data = await fetchData('plans');
    if (!data) return;

    const planListContainer = document.getElementById('plan-list');
    planListContainer.innerHTML = ''; // Clear

    data.plans.forEach(plan => {
        const planElement = document.createElement('div');
        planElement.className = 'dashboard-card mb-4 flex justify-between items-center';
        planElement.innerHTML = `
            <div>
                <h3 class="text-xl font-semibold">${plan.name}</h3>
                <p>$${plan.price} | ${plan.image_limit} images | ${plan.video_limit} videos</p>
            </div>
            <div>
                <button class="text-blue-400 hover:underline">Edit</button>
            </div>
        `;
        planListContainer.appendChild(planElement);
    });
}