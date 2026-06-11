<!-- UNIFIED SETTINGS MODAL (ChatGPT-Style) -->
<div v-cloak v-if="settingsModalOpen"
  style="position:fixed;inset:0;z-index:200;display:flex;align-items:center;justify-content:center;background:rgba(0,0,0,0.6);padding:1rem;"
  @click.self="settingsModalOpen = false">

  <!-- Modal Shell -->
  <div class="settings-modal-shell">

    <!-- Mobile Header (hidden on desktop) -->
    <div class="settings-mobile-header">
      <span style="font-size:16px; font-weight:700; color:var(--modal-text);"
        v-text="settingsActiveTab === 'general' ? 'General' : settingsActiveTab === 'profile' ? 'Profile' : settingsActiveTab === 'appearance' ? 'Appearance' : 'Report Settings'"></span>
    </div>

    <!-- Close Button -->
    <button @click="settingsModalOpen = false"
      style="position:absolute;top:14px;right:14px;z-index:10;background:none;border:none;cursor:pointer;padding:6px;border-radius:8px;color: var(--modal-text-light, #9ca3af);display:flex;align-items:center;justify-content:center;"
      onmouseover="this.style.background='#f3f4f6';this.style.color='#374151'"
      onmouseout="this.style.background='none';this.style.color='#9ca3af'">
      <i data-lucide="x" style="width:18px;height:18px;"></i>
    </button>

    <!-- LEFT SIDEBAR — Tab List -->
    <div class="settings-modal-sidebar">
      <p
        style="font-size:10px;font-weight:700;color: var(--modal-text-light, #9ca3af);letter-spacing:0.08em;text-transform:uppercase;padding:0 10px;margin:0 0 12px 0;">
        Settings</p>

      <!-- General Tab -->
      <button @click="selectSettingsTab('general')" class="settings-tab-btn"
        :class="{ active: settingsActiveTab === 'general' }">
        <i data-lucide="settings-2" style="width:16px;height:16px;flex-shrink:0;"></i>
        <span>General</span>
      </button>

      <!-- Profile Tab -->
      <button @click="selectSettingsTab('profile')" class="settings-tab-btn"
        :class="{ active: settingsActiveTab === 'profile' }">
        <i data-lucide="user" style="width:16px;height:16px;flex-shrink:0;"></i>
        <span>Profile</span>
      </button>

      <!-- Appearance Tab -->
      <button @click="selectSettingsTab('appearance')" class="settings-tab-btn"
        :class="{ active: settingsActiveTab === 'appearance' }">
        <i data-lucide="palette" style="width:16px;height:16px;flex-shrink:0;"></i>
        <span>Appearance</span>
      </button>

      <!-- Report Settings Tab (radtech / radiologist only) -->
      <button v-if="role === 'radtech' || role === 'radiologist'" @click="selectSettingsTab('reports')"
        class="settings-tab-btn" :class="{ active: settingsActiveTab === 'reports' }">
        <i data-lucide="pen-tool" style="width:16px;height:16px;flex-shrink:0;"></i>
        <span>Report Settings</span>
      </button>

      <!-- Divider + App Info at bottom -->
      <div style="margin-top:auto;padding-top:20px;border-top:1px solid var(--modal-border, #e5e7eb);padding-top:16px;">
        <div style="padding:0 10px;">
          <div style="font-size:10px;color:#d1d5db;font-weight:600;text-transform:uppercase;letter-spacing:0.06em;">
            CitiLife System</div>
          <div style="font-size:10px;color:#d1d5db;margin-top:2px;">Radiology Information System</div>
        </div>
      </div>
    </div>

    <!-- RIGHT CONTENT AREA -->
    <div class="settings-modal-content">

      <!-- ======================================= -->
      <!-- TAB: General                            -->
      <!-- ======================================= -->
      <div v-show="settingsActiveTab === 'general'">
        <h3 style="font-size:18px;font-weight:700;color: var(--modal-text, #111827);margin:0 0 16px 0;">General</h3>
        <div style="height:1px;background-color: var(--modal-border-light, #f3f4f6);margin-bottom:24px;"></div>

        <!-- SECTION: Notifications -->
        <p
          style="font-size:10px;font-weight:700;color: var(--modal-text-light, #9ca3af);text-transform:uppercase;letter-spacing:0.08em;margin:0 0 10px 0;">
          Notifications</p>

        <!-- Toggle row: Email Notifications -->
        <div class="settings-flex-row"
          style="padding:14px 0;border-bottom:1px solid var(--modal-border-light, #f3f4f6);">
          <div>
            <div style="font-size:13px;font-weight:600;color: var(--modal-text, #111827);">Email Notifications
            </div>
            <div style="font-size:11px;color: var(--modal-text-light, #9ca3af);margin-top:2px;">Receive case
              updates and alerts via email
            </div>
          </div>
          <label style="position:relative;display:inline-block;width:42px;height:24px;flex-shrink:0;cursor:pointer;">
            <input type="checkbox" v-model="notifEmail" style="opacity:0;width:0;height:0;position:absolute;">
            <span :style="notifEmail
                    ? 'position:absolute;inset:0;border-radius:999px;background:#dc2626;transition:0.2s;'
                    : 'position:absolute;inset:0;border-radius:999px;background:#d1d5db;transition:0.2s;'"></span>
            <span
              :style="notifEmail
                    ? 'position:absolute;top:3px;left:21px;width:18px;height:18px;border-radius:50%;background-color: var(--modal-bg, #fff);transition:0.2s;box-shadow:0 1px 3px rgba(0,0,0,0.2);'
                    : 'position:absolute;top:3px;left:3px;width:18px;height:18px;border-radius:50%;background-color: var(--modal-bg, #fff);transition:0.2s;box-shadow:0 1px 3px rgba(0,0,0,0.2);'"></span>
          </label>
        </div>

        <!-- Toggle row: System Alerts -->
        <div class="settings-flex-row"
          style="padding:14px 0;border-bottom:1px solid var(--modal-border-light, #f3f4f6);">
          <div>
            <div style="font-size:13px;font-weight:600;color: var(--modal-text, #111827);">System Alerts</div>
            <div style="font-size:11px;color: var(--modal-text-light, #9ca3af);margin-top:2px;">Show in-app alerts
              for new cases and updates
            </div>
          </div>
          <label style="position:relative;display:inline-block;width:42px;height:24px;flex-shrink:0;cursor:pointer;">
            <input type="checkbox" v-model="notifSystem" style="opacity:0;width:0;height:0;position:absolute;">
            <span :style="notifSystem
                    ? 'position:absolute;inset:0;border-radius:999px;background:#dc2626;transition:0.2s;'
                    : 'position:absolute;inset:0;border-radius:999px;background:#d1d5db;transition:0.2s;'"></span>
            <span
              :style="notifSystem
                    ? 'position:absolute;top:3px;left:21px;width:18px;height:18px;border-radius:50%;background-color: var(--modal-bg, #fff);transition:0.2s;box-shadow:0 1px 3px rgba(0,0,0,0.2);'
                    : 'position:absolute;top:3px;left:3px;width:18px;height:18px;border-radius:50%;background-color: var(--modal-bg, #fff);transition:0.2s;box-shadow:0 1px 3px rgba(0,0,0,0.2);'"></span>
          </label>
        </div>

        <!-- Toggle row: Sound -->
        <div class="settings-flex-row"
          style="padding:14px 0;border-bottom:1px solid var(--modal-border-light, #f3f4f6);">
          <div>
            <div style="font-size:13px;font-weight:600;color: var(--modal-text, #111827);">Notification Sound
            </div>
            <div style="font-size:11px;color: var(--modal-text-light, #9ca3af);margin-top:2px;">Play a sound when
              a new notification arrives
            </div>
          </div>
          <label style="position:relative;display:inline-block;width:42px;height:24px;flex-shrink:0;cursor:pointer;">
            <input type="checkbox" v-model="notifSound" style="opacity:0;width:0;height:0;position:absolute;">
            <span :style="notifSound
                    ? 'position:absolute;inset:0;border-radius:999px;background:#dc2626;transition:0.2s;'
                    : 'position:absolute;inset:0;border-radius:999px;background:#d1d5db;transition:0.2s;'"></span>
            <span
              :style="notifSound
                    ? 'position:absolute;top:3px;left:21px;width:18px;height:18px;border-radius:50%;background-color: var(--modal-bg, #fff);transition:0.2s;box-shadow:0 1px 3px rgba(0,0,0,0.2);'
                    : 'position:absolute;top:3px;left:3px;width:18px;height:18px;border-radius:50%;background-color: var(--modal-bg, #fff);transition:0.2s;box-shadow:0 1px 3px rgba(0,0,0,0.2);'"></span>
          </label>
        </div>

        <!-- SECTION: Availability (Radiologist Only) -->
        <p v-if="role === 'radiologist'"
          style="font-size:10px;font-weight:700;color: var(--modal-text-light, #9ca3af);text-transform:uppercase;letter-spacing:0.08em;margin:24px 0 10px 0;">
          Availability</p>

        <div v-if="role === 'radiologist'" class="settings-flex-row" style="padding:14px 0;border-bottom:1px solid var(--modal-border-light, #f3f4f6);">
          <div>
            <div style="font-size:13px;font-weight:600;color: var(--modal-text, #111827);">Available for Cases</div>
            <div style="font-size:11px;color: var(--modal-text-light, #9ca3af);margin-top:2px;">Turn this off if you are away. RadTechs won't be able to send you new cases.</div>
          </div>
          <label style="position:relative;display:inline-block;width:42px;height:24px;flex-shrink:0;cursor:pointer;">
            <input type="checkbox" v-model="editIsAvailable" @change="toggleAvailability" style="opacity:0;width:0;height:0;position:absolute;">
            <span :style="editIsAvailable
                    ? 'position:absolute;inset:0;border-radius:999px;background:#dc2626;transition:0.2s;'
                    : 'position:absolute;inset:0;border-radius:999px;background:#d1d5db;transition:0.2s;'"></span>
            <span
              :style="editIsAvailable
                    ? 'position:absolute;top:3px;left:21px;width:18px;height:18px;border-radius:50%;background-color: var(--modal-bg, #fff);transition:0.2s;box-shadow:0 1px 3px rgba(0,0,0,0.2);'
                    : 'position:absolute;top:3px;left:3px;width:18px;height:18px;border-radius:50%;background-color: var(--modal-bg, #fff);transition:0.2s;box-shadow:0 1px 3px rgba(0,0,0,0.2);'"></span>
          </label>
        </div>

        <!-- SECTION: Security -->
        <p
          style="font-size:10px;font-weight:700;color: var(--modal-text-light, #9ca3af);text-transform:uppercase;letter-spacing:0.08em;margin:24px 0 10px 0;">
          Security</p>

        <!-- Change Password -->
        <div class="settings-flex-row"
          style="padding:14px 0;border-bottom:1px solid var(--modal-border-light, #f3f4f6);">
          <div>
            <div style="font-size:13px;font-weight:600;color: var(--modal-text, #111827);">Password</div>
            <div style="font-size:11px;color: var(--modal-text-light, #9ca3af);margin-top:2px;">Update your
              account password</div>
          </div>
          <button @click="requestPasswordReset" :disabled="isRequestingReset"
            style="font-size:12px;font-weight:600;color:#dc2626;text-decoration:none;border:1px solid #fecaca;background:#fef2f2;padding:6px 14px;border-radius:8px;white-space:nowrap;flex-shrink:0;cursor:pointer;"
            :style="isRequestingReset ? 'opacity:0.6;cursor:not-allowed;' : ''">
            {{ isRequestingReset ? 'Sending...' : 'Change Password' }}
          </button>
        </div>

        <!-- Active Session -->
        <div class="settings-flex-row" style="padding:14px 0;">
          <div>
            <div style="font-size:13px;font-weight:600;color: var(--modal-text, #111827);">Active Session</div>
            <div style="font-size:11px;color: var(--modal-text-light, #9ca3af);margin-top:2px;">You are currently
              logged in on this device
            </div>
          </div>
          <span
            style="font-size:11px;font-weight:600;color:#16a34a;background:#f0fdf4;border:1px solid #bbf7d0;padding:4px 10px;border-radius:6px;">●
            Active</span>
        </div>

      </div>

      <!-- ======================================= -->
      <!-- TAB: Profile                            -->
      <!-- ======================================= -->
      <div v-show="settingsActiveTab === 'profile'">
        <h3 style="font-size:18px;font-weight:700;color: var(--modal-text, #111827);margin:0 0 16px 0;">Profile</h3>
        <div style="height:1px;background-color: var(--modal-border-light, #f3f4f6);margin-bottom:24px;"></div>

        <!-- Avatar -->
        <div class="settings-profile-photo-row" style="margin-bottom:24px;">
          <div style="position:relative;flex-shrink:0;">
            <div
              style="width:72px;height:72px;border-radius:50%;background-color: var(--modal-bg-alt, #e5e7eb);color: var(--modal-text, #1f2937);display:flex;align-items:center;justify-content:center;font-size:24px;font-weight:700;overflow:hidden;border:1px solid var(--modal-border-dark, #d1d5db);">
              <template v-if="uploadPreview || userAvatar">
                <img :src="uploadPreview || userAvatar" style="width:100%;height:100%;object-fit:cover;">
              </template>
              <template v-else>{{ userInitials }}</template>
            </div>
            <button @click="$refs.avatarInput.click()"
              style="position:absolute;bottom:-2px;right:-2px;width:26px;height:26px;border-radius:50%;background-color: var(--modal-bg, #fff);border:1px solid var(--modal-border-dark, #d1d5db);display:flex;align-items:center;justify-content:center;cursor:pointer;box-shadow:0 1px 4px rgba(0,0,0,0.15);">
              <i data-lucide="camera" style="width:13px;height:13px;color: var(--modal-text-muted, #6b7280);"></i>
            </button>
            <input type="file" ref="avatarInput" style="display:none;" accept="image/*" @change="handleAvatarChange">
          </div>
          <div>
            <div style="font-size:13px;font-weight:600;color: var(--modal-text-dark, #1f2937);">Profile Photo
            </div>
            <div style="font-size:11px;color: var(--modal-text-light, #9ca3af);margin-top:2px;">Please upload a
              clear, square photo of your
              actual face.</div>
          </div>
        </div>

        <!-- Non-Patient Name Fields -->
        <div v-if="role !== 'patient'"
          style="border:1px solid var(--modal-border, #e5e7eb);border-radius:10px;background-color: var(--modal-bg-alt, #f9fafb);padding:10px 14px;margin-bottom:12px;">
          <label
            style="display:block;font-size:10px;color: var(--modal-text-light, #9ca3af);font-weight:700;text-transform:uppercase;letter-spacing:0.07em;margin-bottom:4px;">Display
            Name</label>
          <input type="text" v-model="editDisplayName"
            style="width:100%;background:transparent;border:none;outline:none;font-size:14px;color: var(--modal-text, #111827);" />
        </div>

        <!-- Patient Name Fields -->
        <div v-if="role === 'patient'" class="settings-grid-cols-2" style="margin-bottom:12px;">
          <div
            style="border:1px solid var(--modal-border, #e5e7eb);border-radius:10px;background-color: var(--modal-bg-alt, #f9fafb);padding:10px 14px;">
            <label
              style="display:block;font-size:10px;color: var(--modal-text-light, #9ca3af);font-weight:700;text-transform:uppercase;letter-spacing:0.07em;margin-bottom:4px;">First
              Name</label>
            <input type="text" v-model="editFirstName"
              style="width:100%;background:transparent;border:none;outline:none;font-size:14px;color: var(--modal-text, #111827);" />
          </div>
          <div
            style="border:1px solid var(--modal-border, #e5e7eb);border-radius:10px;background-color: var(--modal-bg-alt, #f9fafb);padding:10px 14px;">
            <label
              style="display:block;font-size:10px;color: var(--modal-text-light, #9ca3af);font-weight:700;text-transform:uppercase;letter-spacing:0.07em;margin-bottom:4px;">Last
              Name</label>
            <input type="text" v-model="editLastName"
              style="width:100%;background:transparent;border:none;outline:none;font-size:14px;color: var(--modal-text, #111827);" />
          </div>
        </div>

        <!-- Shared Username / Email Field with OTP Flow -->
        <div :style="{ marginBottom: role === 'patient' ? '12px' : '24px' }"
          style="border:1px solid var(--modal-border, #e5e7eb);border-radius:10px;background-color: var(--modal-bg-alt, #f9fafb);padding:10px 14px;">
          <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:4px;">
            <label
              style="display:block;font-size:10px;color: var(--modal-text-light, #9ca3af);font-weight:700;text-transform:uppercase;letter-spacing:0.07em;">Username
              / Email</label>
            <button v-if="emailChangeState === 'idle'" @click="requestEmailChange" type="button"
              style="font-size:11px;color:#dc2626;background:none;border:none;cursor:pointer;font-weight:600;">Change
              Email</button>
            <span v-else-if="emailChangeState === 'sending'"
              style="font-size:11px;color: var(--modal-text-light, #9ca3af);">Sending OTP...</span>
            <button v-else-if="emailChangeState === 'verifying'" @click="emailChangeState = 'idle'" type="button"
              style="font-size:11px;color: var(--modal-text-muted, #6b7280);background:none;border:none;cursor:pointer;font-weight:600;">Cancel</button>
            <span v-else-if="emailChangeState === 'editable'"
              style="font-size:11px;color:#16a34a;font-weight:600;">Verified. Enter new email.</span>
          </div>

          <input type="email" v-model="editEmail" :disabled="emailChangeState !== 'editable'" :style="{
                    width: '100%',
                    background: 'transparent',
                    border: 'none',
                    outline: 'none',
                    fontSize: '14px',
                    color: emailChangeState === 'editable' ? '#111827' : '#6b7280',
                    cursor: emailChangeState === 'editable' ? 'text' : 'not-allowed'
                  }" />

          <div v-if="emailChangeState === 'verifying'"
            style="margin-top:12px;padding-top:12px;border-top:1px dashed var(--modal-border-dark, #d1d5db);">
            <label
              style="display:block;font-size:10px;color: var(--modal-text-light, #9ca3af);font-weight:700;text-transform:uppercase;letter-spacing:0.07em;margin-bottom:4px;">Enter
              OTP sent to {{ userEmail }}</label>
            <div style="display:flex;gap:8px;">
              <input type="text" v-model="otpCode" placeholder="6-digit code" maxlength="6"
                style="flex:1;padding:8px 12px;border-radius:6px;border:1px solid var(--modal-border-dark, #d1d5db);font-size:14px;outline:none;background-color: var(--modal-bg, #fff);color:#000;" />
              <button @click="verifyEmailChangeOtp" type="button"
                style="background:#dc2626;color:#fff;border:none;border-radius:6px;padding:0 16px;font-size:12px;font-weight:600;cursor:pointer;">Verify</button>
            </div>
          </div>
        </div>

        <!-- Patient Specific Additional Fields -->
        <div v-if="role === 'patient'" style="display:flex;flex-direction:column;gap:12px;margin-bottom:24px;">
          <div class="settings-grid-cols-2">
            <div
              style="border:1px solid var(--modal-border, #e5e7eb);border-radius:10px;background-color: var(--modal-bg-alt, #f9fafb);padding:10px 14px;">
              <label
                style="display:block;font-size:10px;color: var(--modal-text-light, #9ca3af);font-weight:700;text-transform:uppercase;letter-spacing:0.07em;margin-bottom:4px;">Birthdate</label>
              <input type="date" v-model="editBirthdate"
                style="width:100%;background:transparent;border:none;outline:none;font-size:14px;color: var(--modal-text, #111827);" />
            </div>
            <div
              style="border:1px solid var(--modal-border, #e5e7eb);border-radius:10px;background-color: var(--modal-bg-alt, #f9fafb);padding:10px 14px;">
              <label
                style="display:block;font-size:10px;color: var(--modal-text-light, #9ca3af);font-weight:700;text-transform:uppercase;letter-spacing:0.07em;margin-bottom:4px;">Sex</label>
              <select v-model="editSex"
                style="width:100%;background:transparent;border:none;outline:none;font-size:14px;color: var(--modal-text, #111827);">
                <option value="Male">Male</option>
                <option value="Female">Female</option>
              </select>
            </div>
          </div>


          <div
            style="border:1px solid var(--modal-border, #e5e7eb);border-radius:10px;background-color: var(--modal-bg-alt, #f9fafb);padding:10px 14px;">
            <label
              style="display:block;font-size:10px;color: var(--modal-text-light, #9ca3af);font-weight:700;text-transform:uppercase;letter-spacing:0.07em;margin-bottom:4px;">Contact
              Number</label>
            <input type="text" v-model="editContactNumber" maxlength="11"
              @input="editContactNumber = editContactNumber.replace(/[^0-9]/g, '')"
              style="width:100%;background:transparent;border:none;outline:none;font-size:14px;color: var(--modal-text, #111827);" />
          </div>

          <div
            style="border:1px solid var(--modal-border, #e5e7eb);border-radius:10px;background-color: var(--modal-bg-alt, #f9fafb);padding:10px 14px;">
            <label
              style="display:block;font-size:10px;color: var(--modal-text-light, #9ca3af);font-weight:700;text-transform:uppercase;letter-spacing:0.07em;margin-bottom:4px;">Home
              Address</label>
            <input type="text" v-model="editHomeAddress"
              style="width:100%;background:transparent;border:none;outline:none;font-size:14px;color: var(--modal-text, #111827);" />
          </div>
        </div>

        <div style="display:flex;justify-content:flex-end;">
          <button @click="saveProfile" :disabled="savingProfile"
            style="background:#dc2626;color:#fff;border:none;padding:10px 22px;border-radius:10px;font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;cursor:pointer;">
            <span v-if="savingProfile">Saving...</span>
            <span v-else>Save Profile</span>
          </button>
        </div>
      </div>

      <!-- ======================================= -->
      <!-- TAB: Appearance                         -->
      <!-- ======================================= -->
      <div v-show="settingsActiveTab === 'appearance'">
        <h3 style="font-size:18px;font-weight:700;color: var(--modal-text, #111827);margin:0 0 16px 0;">Appearance</h3>
        <div style="height:1px;background-color: var(--modal-border-light, #f3f4f6);margin-bottom:24px;"></div>

        <!-- SECTION: Theme -->
        <p
          style="font-size:10px;font-weight:700;color: var(--modal-text-light, #9ca3af);text-transform:uppercase;letter-spacing:0.08em;margin:0 0 10px 0;">
          Appearance</p>

        <!-- Theme row — matches General settings-flex-row style -->
        <div class="settings-flex-row"
          style="padding:14px 0;border-bottom:1px solid var(--modal-border-light, #f3f4f6); position:relative;">
          <div>
            <div style="font-size:13px;font-weight:600;color:var(--modal-text,#111827);">Color Theme</div>
            <div style="font-size:11px;color:var(--modal-text-light,#9ca3af);margin-top:2px;">Choose between light,
              dark, or system default</div>
          </div>
          <!-- Custom dropdown trigger -->
          <div style="position:relative;flex-shrink:0;" ref="themeDropdownRef">
            <button @click="themeDropdownOpen = !themeDropdownOpen"
              style="display:flex;align-items:center;gap:8px;padding:6px 12px;border-radius:8px;border:1px solid var(--modal-border,#e5e7eb);background:var(--modal-bg-alt,#f9fafb);color:var(--modal-text,#111827);cursor:pointer;font-size:12px;font-weight:600;min-width:108px;justify-content:space-between;">
              <span style="display:flex;align-items:center;gap:6px;">
                <span v-show="themeMode === 'system'" style="display:flex;align-items:center;"><i data-lucide="monitor"
                    style="width:14px;height:14px;"></i></span>
                <span v-show="themeMode === 'dark'" style="display:flex;align-items:center;"><i data-lucide="moon"
                    style="width:14px;height:14px;"></i></span>
                <span v-show="themeMode === 'light'" style="display:flex;align-items:center;"><i data-lucide="sun"
                    style="width:14px;height:14px;"></i></span>
                <span>{{ themeMode === 'system' ? 'System' : themeMode === 'dark' ? 'Dark' : 'Light' }}</span>
              </span>
              <i data-lucide="chevron-down" style="width:13px;height:13px;opacity:0.5;"
                :style="themeDropdownOpen ? 'transform:rotate(180deg);transition:transform 0.2s;' : 'transform:rotate(0deg);transition:transform 0.2s;'"></i>
            </button>

            <!-- Dropdown options -->
            <div v-show="themeDropdownOpen"
              style="position:absolute;right:0;top:calc(100% + 6px);z-index:999;min-width:150px;background:var(--modal-bg,#fff);border:1px solid var(--modal-border,#e5e7eb);border-radius:10px;box-shadow:0 8px 24px rgba(0,0,0,0.12);overflow:hidden;display:none;"
              :style="themeDropdownOpen ? 'display:block;' : 'display:none;'">
              <button @click="setTheme('system'); themeDropdownOpen = false"
                :style="themeMode === 'system' ? 'background:var(--modal-bg-alt,#f9fafb);font-weight:600;' : ''"
                style="width:100%;display:flex;align-items:center;justify-content:space-between;gap:10px;padding:10px 14px;border:none;cursor:pointer;font-size:13px;color:var(--modal-text,#111827);text-align:left;background:none;">
                <span style="display:flex;align-items:center;gap:8px;">
                  <i data-lucide="monitor" style="width:14px;height:14px;opacity:0.7;"></i>
                  <span>System</span>
                </span>
                <span v-show="themeMode === 'system'" style="display:flex;align-items:center;"><i data-lucide="check"
                    style="width:14px;height:14px;color:#dc2626;"></i></span>
              </button>
              <button @click="setTheme('light'); themeDropdownOpen = false"
                :style="themeMode === 'light' ? 'background:var(--modal-bg-alt,#f9fafb);font-weight:600;' : ''"
                style="width:100%;display:flex;align-items:center;justify-content:space-between;gap:10px;padding:10px 14px;border:none;cursor:pointer;font-size:13px;color:var(--modal-text,#111827);text-align:left;background:none;">
                <span style="display:flex;align-items:center;gap:8px;">
                  <i data-lucide="sun" style="width:14px;height:14px;opacity:0.7;"></i>
                  <span>Light</span>
                </span>
                <span v-show="themeMode === 'light'" style="display:flex;align-items:center;"><i data-lucide="check"
                    style="width:14px;height:14px;color:#dc2626;"></i></span>
              </button>
              <button @click="setTheme('dark'); themeDropdownOpen = false"
                :style="themeMode === 'dark' ? 'background:var(--modal-bg-alt,#f9fafb);font-weight:600;' : ''"
                style="width:100%;display:flex;align-items:center;justify-content:space-between;gap:10px;padding:10px 14px;border:none;cursor:pointer;font-size:13px;color:var(--modal-text,#111827);text-align:left;background:none;">
                <span style="display:flex;align-items:center;gap:8px;">
                  <i data-lucide="moon" style="width:14px;height:14px;opacity:0.7;"></i>
                  <span>Dark</span>
                </span>
                <span v-show="themeMode === 'dark'" style="display:flex;align-items:center;"><i data-lucide="check"
                    style="width:14px;height:14px;color:#dc2626;"></i></span>
              </button>
            </div>
          </div>
        </div>


      </div>


      <!-- ======================================= -->
      <!-- TAB: Report Settings                    -->
      <!-- ======================================= -->
      <div v-show="settingsActiveTab === 'reports'">
        <h3 style="font-size:18px;font-weight:700;color: var(--modal-text, #111827);margin:0 0 16px 0;">Report Settings
        </h3>
        <div style="height:1px;background-color: var(--modal-border-light, #f3f4f6);margin-bottom:24px;"></div>

        <div
          style="border:1px solid var(--modal-border, #e5e7eb);border-radius:10px;background-color: var(--modal-bg-alt, #f9fafb);padding:10px 14px;margin-bottom:12px;">
          <label
            style="display:block;font-size:10px;color: var(--modal-text-light, #9ca3af);font-weight:700;text-transform:uppercase;letter-spacing:0.07em;margin-bottom:4px;">Full
            Name for Printed Report</label>
          <input type="text" v-model="editFullName" placeholder="Enter your full name for reports..."
            style="width:100%;background:transparent;border:none;outline:none;font-size:14px;color: var(--modal-text, #111827);" />
        </div>

        <div
          style="border:1px solid var(--modal-border, #e5e7eb);border-radius:10px;background-color: var(--modal-bg-alt, #f9fafb);padding:10px 14px;margin-bottom:20px;">
          <label
            style="display:block;font-size:10px;color: var(--modal-text-light, #9ca3af);font-weight:700;text-transform:uppercase;letter-spacing:0.07em;margin-bottom:4px;">Professional
            Titles (e.g. RXT, RRT, MD)</label>
          <input type="text" v-model="editProfessionalTitle" placeholder="Enter your titles..."
            style="width:100%;background:transparent;border:none;outline:none;font-size:14px;color: var(--modal-text, #111827);" />
        </div>

        <div style="margin-bottom:20px;">
          <label
            style="display:block;font-size:10px;color: var(--modal-text-light, #9ca3af);font-weight:700;text-transform:uppercase;letter-spacing:0.07em;margin-bottom:8px;">Digital
            Signature</label>
          <div @click="$refs.signatureInput.click()"
            style="border:2px dashed var(--modal-border-dark, #d1d5db);border-radius:14px;padding:24px;background-color: var(--modal-bg-alt, #f9fafb);cursor:pointer;display:flex;align-items:center;justify-content:center;min-height:120px;"
            onmouseover="this.style.borderColor='#dc2626'" onmouseout="this.style.borderColor='#d1d5db'">
            <div v-if="signaturePreview || userSignature"
              style="display:flex;flex-direction:column;align-items:center;gap:10px;">
              <img :src="signaturePreview || userSignature"
                style="max-height:80px;object-fit:contain;background-color: var(--modal-bg, #fff);border-radius:8px;padding:6px;border:1px solid var(--modal-border, #e5e7eb);">
              <span
                style="font-size:10px;color: var(--modal-text-light, #9ca3af);font-weight:700;text-transform:uppercase;letter-spacing:0.07em;">Click
                to change signature</span>
            </div>
            <div v-else style="display:flex;flex-direction:column;align-items:center;gap:8px;">
              <i data-lucide="pen-tool" style="width:32px;height:32px;color:#d1d5db;"></i>
              <span style="font-size:12px;color: var(--modal-text-light, #9ca3af);">Upload your signature
                image</span>
            </div>
            <input type="file" ref="signatureInput" style="display:none;" accept="image/*"
              @change="handleSignatureChange">
          </div>
          <p style="font-size:10px;color: var(--modal-text-light, #9ca3af);font-style:italic;margin-top:6px;">This
            signature will appear on
            your diagnostic report outputs.</p>
        </div>

        <div style="display:flex;justify-content:flex-end;">
          <button @click="saveRadtechSettings" :disabled="savingRadtechSettings"
            style="background:#dc2626;color:#fff;border:none;padding:10px 22px;border-radius:10px;font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;cursor:pointer;">
            <span v-if="savingRadtechSettings">Saving...</span>
            <span v-else>Save Settings</span>
          </button>
        </div>
      </div>

    </div><!-- end right content -->
  </div><!-- end modal shell -->
</div><!-- end settings modal -->