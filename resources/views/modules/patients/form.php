<?php
use App\Core\Auth;

$actions = '';
require __DIR__ . '/../../components/page-header.php';

$patient = $patient ?? [];
$isEdit = !empty($patient['id']);
$isFrontDesk = Auth::hasRole('receptionist')
    && !Auth::hasRole('super_admin')
    && !Auth::hasRole('admin');

$gender = old('gender', $patient['gender'] ?? '');
$refSelected = old('reference_doctor_id', $patient['reference_doctor_id'] ?? '');
$formAction = $isEdit ? app_url('patients/' . $patient['id']) : app_url('patients');
$cancelUrl = $isEdit ? app_url('patients/' . $patient['id']) : app_url('patients');
$canQuickAddRef = can('patients.edit') || can('patients.add') || can('reference_doctors.add');

$renderReferenceDoctorField = static function (string $selected, array $doctors, bool $canQuickAdd): void {
    ?>
    <label class="form-label d-flex align-items-center justify-content-between gap-2">
        <span>Reference Doctor</span>
        <?php if ($canQuickAdd): ?>
            <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none" data-bs-toggle="modal" data-bs-target="#quickRefDoctorModal">
                <i class="bi bi-plus-circle me-1"></i>Quick Add
            </button>
        <?php endif; ?>
    </label>
    <select class="form-control" name="reference_doctor_id" id="referenceDoctorSelect">
        <option value="">None</option>
        <?php foreach ($doctors as $doc): ?>
            <option value="<?= e($doc['id']) ?>" <?= (string) $selected === (string) $doc['id'] ? 'selected' : '' ?>><?= e($doc['name']) ?></option>
        <?php endforeach; ?>
    </select>
    <?php
};
?>
<form method="post" action="<?= $formAction ?>" class="ajax-form">
    <?= csrf_field() ?>

    <div class="card content-card mb-3">
        <div class="card-body">
            <h3 class="h6">Basic</h3>
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">OPD Number <span class="required-star">*</span></label>
                    <input
                        class="form-control"
                        name="patient_code"
                        value="<?= e(old('patient_code', $patient['patient_code'] ?? ($suggestedOpdNumber ?? ''))) ?>"
                        required
                        placeholder="Auto generated"
                    >
                    <div class="form-text">Auto generated — you can edit if needed.</div>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Name <span class="required-star">*</span></label>
                    <input class="form-control" name="name" value="<?= e(old('name', $patient['name'] ?? '')) ?>" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Mobile <span class="required-star">*</span></label>
                    <input
                        class="form-control"
                        type="text"
                        name="mobile"
                        id="patientMobile"
                        value="<?= e(old('mobile', $patient['mobile'] ?? '')) ?>"
                        required
                        inputmode="numeric"
                        maxlength="11"
                        autocomplete="tel"
                    >
                </div>
                <div class="col-md-2">
                    <label class="form-label">Gender</label>
                    <select class="form-control" name="gender">
                        <?php foreach (['' => 'Select', 'male' => 'Male', 'female' => 'Female', 'other' => 'Other'] as $k => $v): ?>
                            <option value="<?= e($k) ?>" <?= (string) $gender === (string) $k ? 'selected' : '' ?>><?= e($v) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Registration Date</label>
                    <input class="form-control" type="date" name="registration_date" value="<?= e(old('registration_date', $patient['registration_date'] ?? date('Y-m-d'))) ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">DOB</label>
                    <input class="form-control" type="date" name="dob" value="<?= e(old('dob', $patient['dob'] ?? '')) ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Age</label>
                    <input class="form-control" type="number" name="age" value="<?= e(old('age', $patient['age'] ?? '')) ?>">
                </div>

                <?php if ($isFrontDesk): ?>
                    <div class="col-md-4">
                        <?php $renderReferenceDoctorField($refSelected, $referenceDoctors ?? [], $canQuickAddRef); ?>
                    </div>
                <?php else: ?>
                    <div class="col-md-2">
                        <label class="form-label">Blood Group</label>
                        <input class="form-control" name="blood_group" value="<?= e(old('blood_group', $patient['blood_group'] ?? '')) ?>">
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php if (!$isFrontDesk): ?>
        <div class="card content-card mb-3">
            <div class="card-body">
                <h3 class="h6">Contact</h3>
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Alternate Mobile</label>
                        <input class="form-control" name="alternate_mobile" value="<?= e(old('alternate_mobile', $patient['alternate_mobile'] ?? '')) ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Email</label>
                        <input class="form-control" type="email" name="email" value="<?= e(old('email', $patient['email'] ?? '')) ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Emergency Contact</label>
                        <input class="form-control" name="emergency_contact" value="<?= e(old('emergency_contact', $patient['emergency_contact'] ?? '')) ?>">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Address</label>
                        <textarea class="form-control" name="address"><?= e(old('address', $patient['address'] ?? '')) ?></textarea>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">City</label>
                        <input class="form-control" name="city" value="<?= e(old('city', $patient['city'] ?? '')) ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">State</label>
                        <input class="form-control" name="state" value="<?= e(old('state', $patient['state'] ?? '')) ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Pincode</label>
                        <input class="form-control" name="pincode" value="<?= e(old('pincode', $patient['pincode'] ?? '')) ?>">
                    </div>
                </div>
            </div>
        </div>

        <div class="card content-card mb-3">
            <div class="card-body">
                <h3 class="h6">Medical</h3>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Medical History</label>
                        <textarea class="form-control" name="medical_history"><?= e(old('medical_history', $patient['medical_history'] ?? '')) ?></textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Allergies</label>
                        <textarea class="form-control" name="allergies"><?= e(old('allergies', $patient['allergies'] ?? '')) ?></textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Existing Conditions</label>
                        <textarea class="form-control" name="existing_conditions"><?= e(old('existing_conditions', $patient['existing_conditions'] ?? '')) ?></textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Current Medicines</label>
                        <textarea class="form-control" name="current_medicines"><?= e(old('current_medicines', $patient['current_medicines'] ?? '')) ?></textarea>
                    </div>
                </div>
            </div>
        </div>

        <div class="card content-card">
            <div class="card-body">
                <h3 class="h6">Reference</h3>
                <div class="row g-3">
                    <div class="col-md-4">
                        <?php $renderReferenceDoctorField($refSelected, $referenceDoctors ?? [], $canQuickAddRef); ?>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label">Notes</label>
                        <textarea class="form-control" name="notes"><?= e(old('notes', $patient['notes'] ?? '')) ?></textarea>
                    </div>
                </div>
                <div class="mt-4 d-flex gap-2 flex-wrap">
                    <button type="submit" class="btn btn-primary" name="submit_action" value="save">Save</button>
                    <?php if (!$isEdit): ?>
                        <button type="submit" class="btn btn-outline-primary" name="submit_action" value="save_new">Save & New</button>
                        <button type="submit" class="btn btn-outline-primary" name="submit_action" value="book">Save & Book</button>
                    <?php endif; ?>
                    <a class="btn btn-light" href="<?= $cancelUrl ?>">Cancel</a>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="card content-card">
            <div class="card-body">
                <div class="d-flex gap-2 flex-wrap">
                    <button type="submit" class="btn btn-primary" name="submit_action" value="save">Save</button>
                    <?php if (!$isEdit): ?>
                        <button type="submit" class="btn btn-outline-primary" name="submit_action" value="save_new">Save &amp; New</button>
                    <?php endif; ?>
                    <a class="btn btn-light" href="<?= $cancelUrl ?>">Cancel</a>
                </div>
            </div>
        </div>
    <?php endif; ?>
</form>

<?php if ($canQuickAddRef): ?>
<div class="modal fade" id="quickRefDoctorModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Quick Add Reference Doctor</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="quickRefDoctorForm">
                <div class="modal-body">
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label class="form-label">Name <span class="required-star">*</span></label>
                        <input type="text" class="form-control" name="name" id="quickRefName" required maxlength="150" placeholder="Doctor name">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Mobile</label>
                        <input type="text" class="form-control" name="mobile" id="quickRefMobile" inputmode="numeric" maxlength="11">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Clinic / Hospital</label>
                        <input type="text" class="form-control" name="clinic_hospital" id="quickRefClinic" maxlength="255">
                    </div>
                    <div class="mb-0">
                        <label class="form-label">Specialization</label>
                        <input type="text" class="form-control" name="specialization" id="quickRefSpec" maxlength="255">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="quickRefSaveBtn">Add Doctor</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
(function () {
  const form = document.querySelector('form.ajax-form');
  const mobile = document.getElementById('patientMobile');
  if (form && mobile) {
    function digitsOnly(value) {
      return String(value || '').replace(/\D+/g, '');
    }

    function isValidMobile(value) {
      const digits = digitsOnly(value);
      return digits.length === 10 || digits.length === 11;
    }

    mobile.addEventListener('input', function () {
      const cleaned = digitsOnly(this.value).slice(0, 11);
      if (this.value !== cleaned) {
        this.value = cleaned;
      }
    });

    form.addEventListener('submit', function (e) {
      const value = mobile.value;
      if (isValidMobile(value)) {
        mobile.value = digitsOnly(value);
        return;
      }
      e.preventDefault();
      e.stopImmediatePropagation();
      if (window.toastr) {
        toastr.error('Mobile number must be 10 or 11 digits.');
      }
      mobile.focus();
      mobile.classList.add('is-invalid');
    }, true);

    mobile.addEventListener('focus', function () {
      mobile.classList.remove('is-invalid');
    });
  }

  const quickForm = document.getElementById('quickRefDoctorForm');
  const select = document.getElementById('referenceDoctorSelect');
  if (!quickForm || !select) return;

  const quickUrl = <?= json_encode(app_url('reference-doctors/quick')) ?>;
  const modalEl = document.getElementById('quickRefDoctorModal');

  quickForm.addEventListener('submit', function (e) {
    e.preventDefault();
    e.stopPropagation();
    const nameInput = document.getElementById('quickRefName');
    const name = (nameInput && nameInput.value || '').trim();
    if (!name) {
      toastr.error('Reference doctor name is required.');
      nameInput && nameInput.focus();
      return;
    }

    const btn = document.getElementById('quickRefSaveBtn');
    const original = btn ? btn.innerHTML : '';
    if (btn) {
      btn.disabled = true;
      btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Saving...';
    }

    const fd = new FormData(quickForm);
    fetch(quickUrl, {
      method: 'POST',
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': window.CSRF_TOKEN || ''
      },
      body: fd
    }).then(function (r) { return r.json().then(function (data) { return { ok: r.ok, data: data }; }); })
      .then(function (res) {
        if (!res.ok || !res.data || res.data.success === false) {
          toastr.error((res.data && res.data.message) || 'Unable to add reference doctor.');
          return;
        }
        const id = res.data.data && res.data.data.id;
        const doctorName = (res.data.data && res.data.data.name) || name;
        if (!id) {
          toastr.error('Reference doctor saved but ID missing. Refresh and try again.');
          return;
        }
        const opt = document.createElement('option');
        opt.value = String(id);
        opt.textContent = doctorName;
        opt.selected = true;
        select.appendChild(opt);
        select.value = String(id);
        if (window.jQuery && jQuery.fn.select2 && jQuery(select).hasClass('select2-hidden-accessible')) {
          jQuery(select).val(String(id)).trigger('change');
        }
        toastr.success(res.data.message || 'Reference doctor added.');
        quickForm.reset();
        if (modalEl && window.bootstrap) {
          bootstrap.Modal.getInstance(modalEl)?.hide();
        }
      })
      .catch(function () {
        toastr.error('Unable to add reference doctor.');
      })
      .finally(function () {
        if (btn) {
          btn.disabled = false;
          btn.innerHTML = original || 'Add Doctor';
        }
      });
  });
})();
</script>
