// Global state variables
let currentUser = null;
window.teaProducts = [];

// --- HELPER FUNCTIONS ---

function getTodayDateString() {
  return new Date().toISOString().split('T')[0];
}

function showMessage(elementId, message, type = 'success') {
  const element = document.getElementById(elementId);
  if (!element) return;
  element.textContent = message;
  element.className = type === 'success' ? 'success-message' : 'error-message';
  element.style.display = 'block';
  setTimeout(() => {
    element.style.display = 'none';
  }, 4000);
}

function showError(message) {
  console.error(message);
  alert(message);
}

// --- PAGE INITIALIZATION ---

document.addEventListener('DOMContentLoaded', async () => {
  // 1. Initial UI Setup
  document.getElementById('currentDate').textContent =
    new Date().toLocaleDateString('en-CA');
  document.getElementById('inventoryDate').value = getTodayDateString();

  // 2. Authenticate and set up user-specific UI
  await fetchCurrentUser();

  // 3. Load core application data (if login was successful)
  if (currentUser) {
    await loadTeaProducts();
    // 4. Load content for the default visible tab
    loadInventoryGrid();
  }
});

// --- AUTHENTICATION & USER MANAGEMENT ---

async function fetchCurrentUser() {
  try {
    const response = await fetch('api/session_status.php');
    const result = await response.json();
    if (result.status === 'success' && result.loggedin) {
      currentUser = result.user;
      setupUserInterface();
    } else {
      window.location.href = 'login.html';
    }
  } catch (error) {
    console.error('Could not fetch session status:', error);
    window.location.href = 'login.html';
  }
}

function setupUserInterface() {
  if (!currentUser) return;
  const header = document.querySelector('.header h1');
  if (header) {
    header.innerHTML = `🍃 Tea Tracker <span style="font-size: 0.6em; color: #666;">(User: ${currentUser.username})</span>`;
  }
  const accountTabButton = document.querySelector(
    "button[onclick=\"showTab('account')\"]"
  );
  if (accountTabButton) {
    accountTabButton.style.display = 'inline-block';
  }
  //Show dashboard tab ONLY for executives
  if (currentUser.role === 'executive') {
    const dashboardTabButton = document.querySelector("button[onclick=\"showTab('dashboard')\"]");
    if (dashboardTabButton) {
        dashboardTabButton.style.display = 'inline-block';
    }
  }

  loadAccountTab();
}

function handleLogout() {
  fetch('api/logout.php', { method: 'POST' }).finally(() => {
    window.location.href = 'login.html';
  });
}

// --- TAB CONTROLLER ---

function showTab(tabName) {
  document.querySelectorAll('.tab-content').forEach(content => {
    content.classList.remove('active');
  });
  document.querySelectorAll('.tab').forEach(tab => {
    tab.classList.remove('active');
  });

  document.getElementById(tabName).classList.add('active');
  const activeButton = document.querySelector(
    `button[onclick="showTab('${tabName}')"]`
  );
  if (activeButton) {
    activeButton.classList.add('active');
  }

  // Controller logic: Load data for the now-visible tab
  if (tabName === 'inventory') {
    loadInventoryGrid();
  } else if (tabName === 'sales') {
    loadSalesSection();
  } else if (tabName === 'summary') {
    refreshSummary();
  } else if (tabName === 'manage') {
    loadTeaList();
  } else if (tabName === 'dashboard') { 
    loadDashboard();
  }
}

// --- DATA LOADING & DISPLAY FUNCTIONS ---

// THIS IS THE CORRECTED VERSION
async function loadTeaProducts() {
  try {
    const response = await fetch('api/products.php');
    const result = await response.json();
    if (result.status === 'success') {
      window.teaProducts = result.data;
    } else {
      showError('Failed to load tea products: ' + result.message);
      window.teaProducts = []; // Ensure it's an empty array on failure
    }
  } catch (error) {
    showError('Network error loading tea products: ' + error.message);
    window.teaProducts = [];
  }
}

function loadInventoryGrid() {
  const datePicker = document.getElementById('inventoryDate');
  if (!datePicker.value) datePicker.value = getTodayDateString();
  const selectedDate = datePicker.value;

  const grid = document.getElementById('inventoryGrid');
  grid.innerHTML = 'Loading inventory...';

  fetch(`api/inventory.php?date=${selectedDate}`)
    .then(response => response.json())
    .then(result => {
      grid.innerHTML = ''; // Clear loading message
      const inventory = result.status === 'success' ? result.data : [];
      if (window.teaProducts.length === 0) {
        grid.innerHTML =
          '<p>No tea products found. Add some in the Manage Teas tab.</p>';
        return;
      }
      window.teaProducts.forEach(tea => {
        const teaInventory = inventory.find(
          inv => inv.tea_product_id == tea.id
        );
        const card = document.createElement('div');
        card.className = 'tea-card';
        card.innerHTML = `
          <div class="tea-name">${tea.name}</div>
          <div class="tea-prices">
              <span>Buy: $${parseFloat(tea.vendor_cost).toFixed(2)}</span>
              <span>Sell: $${parseFloat(tea.selling_price).toFixed(2)}</span>
          </div>
          <input type="number" class="inventory-input" 
                 placeholder="Quantity for ${selectedDate}" 
                 min="0" 
                 value="${
                   teaInventory ? teaInventory.quantity_purchased : ''
                 }"
                 onchange="updateInventory(${tea.id}, this.value)">
          <small>${tea.description || ''}</small>
        `;
        grid.appendChild(card);
      });
    })
    .catch(error => {
      showError('Error loading inventory: ' + error.message);
      grid.innerHTML = `<p class="error-message">Could not load inventory.</p>`;
    });
}

function loadSalesSection() {
  const datePicker = document.getElementById('salesDate');
  if (!datePicker.value) datePicker.value = getTodayDateString();
  const selectedDate = datePicker.value;

  const section = document.getElementById('salesSection');
  section.innerHTML = 'Loading sales terminal...';

  fetch(`api/inventory.php?date=${selectedDate}`)
    .then(response => response.json())
    .then(result => {
      section.innerHTML = '';
      const inventory = result.status === 'success' ? result.data : [];
      if (window.teaProducts.length === 0) {
        section.innerHTML =
          '<p>No tea products found. Add some in the Manage Teas tab.</p>';
        return;
      }
      window.teaProducts.forEach(tea => {
        const teaInventory = inventory.find(
          inv => inv.tea_product_id == tea.id
        );
        const stockLevel = teaInventory ? teaInventory.quantity_remaining : 0;
        let stockClass =
          stockLevel > 5
            ? 'stock-high'
            : stockLevel > 2
            ? 'stock-medium'
            : 'stock-low';

        const card = document.createElement('div');
        card.className = 'sales-card';
        card.innerHTML = `
          <div class="tea-name">${tea.name}</div>
          <div class="stock-display ${stockClass}">Stock: ${stockLevel}</div>
          <input type="number" id="customPrice_${
            tea.id
          }" value="${parseFloat(tea.selling_price).toFixed(
          2
        )}" class="inventory-input" style="text-align: center;">
          <button class="sell-button" onclick="sellTea(${
            tea.id
          })" ${stockLevel <= 0 ? 'disabled' : ''}>
              ${stockLevel <= 0 ? 'Out of Stock' : 'Sell One'}
          </button>
        `;
        section.appendChild(card);
      });
    })
    .catch(error => {
      showError('Error loading sales data: ' + error.message);
      section.innerHTML = `<p class="error-message">Could not load sales data.</p>`;
    });
}

function refreshSummary() {
  const datePicker = document.getElementById('summaryDate');
  if (!datePicker.value) datePicker.value = getTodayDateString();
  const selectedDate = datePicker.value;

  // Add onchange listener if it doesn't exist
  if (!datePicker.onchange) {
    datePicker.onchange = refreshSummary;
  }

  fetch(`api/summary.php?date=${selectedDate}`)
    .then(response => response.json())
    .then(result => {
      if (result.status === 'success') {
        const summary = result.data.summary;
        document.getElementById('totalCost').textContent = `$${parseFloat(
          summary.total_inventory_cost
        ).toFixed(2)}`;
        document.getElementById('totalRevenue').textContent = `$${parseFloat(
          summary.total_revenue
        ).toFixed(2)}`;
        document.getElementById('totalProfit').textContent = `$${parseFloat(
          summary.total_profit
        ).toFixed(2)}`;
        document.getElementById('totalSold').textContent =
          summary.total_units_sold;
      } else {
        showError('Failed to load summary: ' + result.message);
      }
    })
    .catch(error => showError('Error refreshing summary: ' + error.message));
}

function loadDashboard() {
  // Security check on the frontend as well
  if (currentUser.role !== 'executive') {
    document.getElementById('dashboard').innerHTML = '<h2>Access Denied</h2>';
    return;
  }

  const datePicker = document.getElementById('dashboardDate');
  if (!datePicker.value) {
    datePicker.value = getTodayDateString();
  }
  // Add listener to reload data when date changes
  datePicker.onchange = loadDashboard;

  const selectedDate = datePicker.value;
  const tableBody = document.getElementById('dashboardUserTableBody');
  tableBody.innerHTML = '<tr><td colspan="6" style="text-align: center; padding: 20px;">Loading report...</td></tr>';

  fetch(`api/executive_report.php?date=${selectedDate}`)
    .then(response => response.json())
    .then(result => {
      if (result.status === 'success') {
        const { grand_totals, user_breakdown } = result.data;

        // Populate Grand Totals
        document.getElementById('dashTotalCost').textContent = `$${parseFloat(grand_totals.total_inventory_cost).toFixed(2)}`;
        document.getElementById('dashTotalRevenue').textContent = `$${parseFloat(grand_totals.total_revenue).toFixed(2)}`;
        document.getElementById('dashTotalProfit').textContent = `$${parseFloat(grand_totals.total_profit).toFixed(2)}`;
        document.getElementById('dashTotalSold').textContent = grand_totals.total_units_sold;

        // Populate User Breakdown Table
        if (user_breakdown.length === 0) {
          tableBody.innerHTML = '<tr><td colspan="6" style="text-align: center; padding: 20px;">No data found for this date.</td></tr>';
          return;
        }

        let tableHtml = '';
        user_breakdown.forEach(user => {
          tableHtml += `
            <tr style="border-bottom: 1px solid #eee;">
              <td style="padding: 12px; text-align: left;"><strong>${user.username}</strong></td>
              <td style="padding: 12px; text-align: right;">$${parseFloat(user.total_inventory_cost).toFixed(2)}</td>
              <td style="padding: 12px; text-align: right;">$${parseFloat(user.total_revenue).toFixed(2)}</td>
              <td style="padding: 12px; text-align: right; font-weight: bold; color: ${user.profit >= 0 ? '#28a745' : '#dc3545'};">$${parseFloat(user.profit).toFixed(2)}</td>
              <td style="padding: 12px; text-align: right;">${user.total_units_purchased}</td>
              <td style="padding: 12px; text-align: right;">${user.total_units_sold}</td>
            </tr>
          `;
        });
        tableBody.innerHTML = tableHtml;

      } else {
        showError('Failed to load dashboard: ' + result.message);
        tableBody.innerHTML = `<tr><td colspan="6" class="error-message" style="display: table-cell;">${result.message}</td></tr>`;
      }
    })
    .catch(error => {
        showError('Network error loading dashboard: ' + error.message);
        tableBody.innerHTML = `<tr><td colspan="6" class="error-message" style="display: table-cell;">A network error occurred.</td></tr>`;
    });
}
// --- ACCOUNT TAB FUNCTIONS ---

function loadAccountTab() {
  const container = document.getElementById('account');
  if (!container) return;
  let executiveHtml = '';
  if (currentUser.role === 'executive') {
    executiveHtml = `
      <div class="summary-card">
          <h3>Register New Employee</h3>
          <input type="text" id="newUsername" placeholder="New Employee Username" class="inventory-input" style="margin-bottom: 10px;">
          <input type="password" id="newPassword" placeholder="Temporary Password" class="inventory-input">
          <button class="action-button" onclick="handleRegister()">Register Employee</button>
          <div id="registerMessage" style="margin-top: 10px;"></div>
      </div>
      <div class="summary-card">
          <h3>All Users</h3>
          <div id="userList">Loading users...</div>
      </div>
    `;
    loadUserList();
  }
  container.innerHTML = `
    <h2>Account Management</h2>
    <div class="summary-card">
        <h3>Change Your Password</h3>
        <input type="password" id="currentPassword" placeholder="Current Password" class="inventory-input" style="margin-bottom: 10px;">
        <input type="password" id="newPasswordForChange" placeholder="New Password" class="inventory-input">
        <button class="action-button" onclick="handleChangePassword()">Update Password</button>
        <div id="passwordChangeMessage" style="margin-top: 10px;"></div>
    </div>
    ${executiveHtml}
  `;
}

function loadUserList() {
  fetch('api/users.php')
    .then(response => response.json())
    .then(result => {
      const userListDiv = document.getElementById('userList');
      if (!userListDiv) return;
      if (result.status === 'success') {
        userListDiv.innerHTML = result.data
          .map(user => {
            const deleteButtonHtml =
              user.id === currentUser.id
                ? ''
                : `<button class="delete-button" onclick="handleDeleteUser(${user.id}, '${user.username}')">🗑️ Delete</button>`;
            return `
              <div class="tea-item">
                  <div class="tea-details">
                      <strong>${user.username}</strong> (${user.role})
                      <div style="font-size: 0.9em; color: #666;">Joined: ${new Date(
                        user.created_at
                      ).toLocaleDateString()}</div>
                  </div>
                  <div>${deleteButtonHtml}</div>
              </div>`;
          })
          .join('');
      } else {
        userListDiv.innerHTML = `<p class="error-message">${result.message}</p>`;
      }
    });
}

function handleRegister() {
  const username = document.getElementById('newUsername').value.trim();
  const password = document.getElementById('newPassword').value;
  fetch('api/register.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ username, password }),
  })
    .then(response => response.json())
    .then(result => {
      if (result.status === 'success') {
        showMessage('registerMessage', result.message, 'success');
        loadUserList();
      } else {
        showMessage('registerMessage', result.message, 'error');
      }
    });
}

function handleChangePassword() {
  const current_password = document.getElementById('currentPassword').value;
  const new_password = document.getElementById('newPasswordForChange').value;
  fetch('api/change_password.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ current_password, new_password }),
  })
    .then(response => response.json())
    .then(result => {
      if (result.status === 'success') {
        showMessage('passwordChangeMessage', result.message, 'success');
      } else {
        showMessage('passwordChangeMessage', result.message, 'error');
      }
    });
}

function handleDeleteUser(userId, username) {
  if (
    !confirm(
      `Are you sure you want to permanently delete the user "${username}"?\nAll of their sales and inventory data will be lost forever. This cannot be undone.`
    )
  ) {
    return;
  }
  fetch('api/delete_user.php', {
    method: 'DELETE',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ id: userId }),
  })
    .then(response => response.json())
    .then(result => {
      if (result.status === 'success') {
        showMessage('registerMessage', result.message, 'success');
        loadUserList();
      } else {
        showMessage('registerMessage', result.message, 'error');
      }
    });
}

// --- DATA MUTATION FUNCTIONS ---

function updateInventory(teaId, quantity) {
  const selectedDate = document.getElementById('inventoryDate').value;
  const data = {
    tea_product_id: teaId,
    quantity_purchased: parseInt(quantity) || 0,
    market_date: selectedDate,
  };
  fetch('api/inventory.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(data),
  })
    .then(response => response.json())
    .then(result => {
      if (result.status === 'success') {
        showMessage('inventoryMessage', 'Inventory updated.', 'success');
        // When inventory is updated, the sales terminal for that day might change
      } else {
        showMessage('inventoryMessage', 'Error: ' + result.message, 'error');
      }
    });
}
// Add this function back into assets/js/app.js

function saveInventory() {
    // This function is primarily for user feedback, as data saves on change.
    showMessage('inventoryMessage', 'All changes have been saved.', 'success');
}

// In assets/js/app.js

function sellTea(teaId) {
  const selectedDate = document.getElementById('salesDate').value;
  const customPrice =
    parseFloat(document.getElementById(`customPrice_${teaId}`).value) || 0;

  if (customPrice <= 0) {
    alert('Please enter a valid price.');
    return;
  }

  const data = {
    tea_product_id: teaId,
    quantity_sold: 1,
    market_date: selectedDate,
    unit_price: customPrice,
  };

  fetch('api/sales.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(data),
  })
    .then(response => {
        // First, check if the response is OK. If not, it's a server error.
        if (!response.ok) {
            // This will catch 500 errors from PHP crashes
            throw new Error(`Network response was not ok: ${response.statusText}`);
        }
        return response.json();
    })
    .then(result => {
      if (result.status === 'success') {
        showMessage('salesMessage', 'Sale recorded!', 'success');
        // CORRECT: Only refresh the current tab's content.
        loadSalesSection();
        // The incorrect call to refreshSummary() has been removed.
      } else {
        // This handles logical errors from the API, like "out of stock".
        showMessage('salesMessage', 'Error: ' + result.message, 'error');
        alert('Sale failed: ' + result.message);
      }
    })
    .catch(error => {
        // This .catch() is for network failures or the thrown error from a bad response.
        console.error("Sell Tea Error:", error);
        alert("A critical error occurred while trying to record the sale. Please check the console.");
    });
}

// --- MANAGE TEAS FUNCTIONS ---

function loadTeaList() {
  const teaList = document.getElementById('teaList');
  if (!teaList) return;
  teaList.innerHTML = '';
  if (!window.teaProducts || window.teaProducts.length === 0) {
    teaList.innerHTML = '<p>No teas added yet.</p>';
    return;
  }
  window.teaProducts.forEach(tea => {
    const teaItem = document.createElement('div');
    teaItem.className = 'tea-item';
    teaItem.innerHTML = `
      <div class="tea-details">
          <strong>${tea.name}</strong>
          <div class="tea-pricing">
              <span>Buy: $${parseFloat(tea.vendor_cost).toFixed(2)}</span>
              <span>Sell: $${parseFloat(tea.selling_price).toFixed(2)}</span>
          </div>
      </div>
      <div>
          <button class="action-button" style="background: #ffc107; margin-right: 5px;" onclick="showEditModal(${
            tea.id
          })">✏️ Edit</button>
          <button class="delete-button" onclick="deleteTea(${
            tea.id
          })">🗑️ Delete</button>
      </div>
    `;
    teaList.appendChild(teaItem);
  });
}

function addNewTea() {
  const name = document.getElementById('newTeaName').value.trim();
  const description = document.getElementById('newTeaDescription').value.trim();
  const vendor_cost = parseFloat(
    document.getElementById('newTeaBuyCost').value
  );
  const selling_price = parseFloat(
    document.getElementById('newTeaSellPrice').value
  );

  if (!name || isNaN(vendor_cost) || isNaN(selling_price)) {
    showMessage(
      'manageMessage',
      'Name, Buy Cost, and Sell Price are required.',
      'error'
    );
    return;
  }

  const data = { name, description, vendor_cost, selling_price };

  fetch('api/add_product.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(data),
  })
    .then(response => response.json())
    .then(async result => {
      if (result.status === 'success') {
        showMessage('manageMessage', result.message, 'success');
        document.getElementById('newTeaName').value = '';
        document.getElementById('newTeaDescription').value = '';
        document.getElementById('newTeaBuyCost').value = '';
        document.getElementById('newTeaSellPrice').value = '';
        await loadTeaProducts(); // Refresh global product list
        loadTeaList(); // Re-render the list in the UI
      } else {
        showMessage('manageMessage', 'Error: ' + result.message, 'error');
      }
    });
}

function deleteTea(teaId) {
  if (confirm('Are you sure you want to delete this tea?')) {
    fetch('api/delete_product.php', {
      method: 'DELETE',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ id: teaId }),
    })
      .then(response => response.json())
      .then(async result => {
        if (result.status === 'success') {
          showMessage('manageMessage', result.message, 'success');
          await loadTeaProducts();
          loadTeaList();
        } else {
          showMessage('manageMessage', 'Error: ' + result.message, 'error');
        }
      });
  }
}

function showEditModal(teaId) {
  const teaToEdit = window.teaProducts.find(tea => tea.id === teaId);
  if (!teaToEdit) return;

  const modalOverlay = document.createElement('div');
  modalOverlay.id = 'editModal';
  Object.assign(modalOverlay.style, {
    position: 'fixed',
    top: '0',
    left: '0',
    width: '100%',
    height: '100%',
    backgroundColor: 'rgba(0, 0, 0, 0.6)',
    display: 'flex',
    justifyContent: 'center',
    alignItems: 'center',
    zIndex: '1000',
  });

  modalOverlay.innerHTML = `
    <div class="summary-card" style="width: 90%; max-width: 500px;">
        <h2>Edit ${teaToEdit.name}</h2>
        <div style="margin-top: 20px;">
            <label>Tea Name</label>
            <input type="text" id="editTeaName" class="inventory-input" value="${
              teaToEdit.name
            }">
            <label>Description</label>
            <textarea id="editTeaDescription" class="inventory-input" rows="3">${
              teaToEdit.description || ''
            }</textarea>
            <label>Buy Cost ($)</label>
            <input type="number" id="editTeaBuyCost" class="inventory-input" step="0.01" value="${parseFloat(
              teaToEdit.vendor_cost
            ).toFixed(2)}">
            <label>Sell Price ($)</label>
            <input type="number" id="editTeaSellPrice" class="inventory-input" step="0.01" value="${parseFloat(
              teaToEdit.selling_price
            ).toFixed(2)}">
        </div>
        <div style="margin-top: 25px; text-align: right;">
            <button class="action-button" style="background: #6c757d;" onclick="closeEditModal()">Cancel</button>
            <button class="action-button" onclick="handleUpdateTea(${
              teaToEdit.id
            })">💾 Save Changes</button>
        </div>
    </div>
  `;
  document.body.appendChild(modalOverlay);
}

function closeEditModal() {
  const modalOverlay = document.getElementById('editModal');
  if (modalOverlay) modalOverlay.remove();
}

function handleUpdateTea(teaId) {
  const updatedData = {
    name: document.getElementById('editTeaName').value.trim(),
    description: document.getElementById('editTeaDescription').value.trim(),
    vendor_cost: parseFloat(document.getElementById('editTeaBuyCost').value),
    selling_price: parseFloat(
      document.getElementById('editTeaSellPrice').value
    ),
  };

  if (
    !updatedData.name ||
    isNaN(updatedData.vendor_cost) ||
    isNaN(updatedData.selling_price)
  ) {
    alert('Please ensure all fields have valid values.');
    return;
  }

  fetch(`api/update_product.php?id=${teaId}`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(updatedData),
  })
    .then(response => response.json())
    .then(async result => {
      if (result.status === 'success') {
        showMessage('manageMessage', result.message, 'success');
        closeEditModal();
        await loadTeaProducts();
        loadTeaList();
      } else {
        alert('Error: ' + result.message);
      }
    });
    s


}