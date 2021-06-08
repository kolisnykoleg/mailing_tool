let addressList = $('#addressList').DataTable({
  ajax: {
    url: '/address/roll',
    type: 'POST',
    data: data => {
      data.pools = $('#poolSelect').val()
      data.duplicates = $('#duplicatesSelect').val()
      return data
    },
  },
  columns: [
    { data: 'company' },
    { data: 'street' },
    { data: 'zip' },
    { data: 'city' },
    { data: 'country' },
    { data: 'gender' },
    { data: 'firstName' },
    { data: 'lastName' },
    { data: 'phone' },
    { data: 'status' },
    { data: 'reaction.name' },
    {
      data: 'reaction.id',
      visible: false,
    },
    {
      orderable: false,
      targets: 0,
      data: null,
      render: data => {
        return `<button class="btn btn-sm btn-primary edit-address-btn mr-1" data-id="${data.id}"><i class="fa fa-edit"></i></button>`
      }
    },
  ],
  buttons: [
    {
      extend: 'csvHtml5',
      className: 'mb-2',
      exportOptions: {
        columns: [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10],
      },
    },
  ],
  initComplete: function () {
    $('.col-md-6:eq(0)', $(this).DataTable().table().container())
      .append($('<button class="btn btn-primary mb-2 mr-2" data-toggle="modal" data-target="#createAddressFormModal">Add address</button>'))
      .append($('<button class="btn btn-primary mb-2 mr-2" id="searchBarToggle">Show filters</button>'))
      .append($('<button class="btn btn-primary mb-2 mr-2" id="clearSearch">Clear filters</button>'))
      .append($(this).DataTable().buttons().container())
  },
  createdRow: function (row, data) {
    $(row).attr('id', 'row_' + data.id)

    $(row).css('background', data.pool.color || 'white')

    if (data.blacklist) {
      $(row).addClass('blacklist')
    }

    if (+data.reaction.id > 1) {
      $(row).addClass('bg-green')
    }
  },
  paging: true,
  pageLength: 50,
  info: true,
  searching: true,
})

$('.search-col').each(function (i) {
  $(this).on('keyup change', function () {
    if (addressList.column(i).search() !== this.value) {
      addressList
        .column(i)
        .search(this.value)
        .draw()
    }
  })
})

$(document).on('click', '#clearSearch', function () {
  $('.search-col').val('').trigger('change')
  $('#reactionSelectSearch').val('').trigger('change')
  $('#genderSelectSearch').val('').trigger('change')
  $('#statusSelectSearch').val('').trigger('change')
})

$('#reactionSelectSearch').on('change', function () {
  const reactionID = $(this).val() || ''
  addressList
    .column(11)
    .search(reactionID)
    .draw()
})

$('#genderSelectSearch').select2({
  theme: 'bootstrap4',
}).on('change', function () {
  const gender = $(this).val() || ''
  addressList
    .columns(5)
    .search(gender, gender)
    .draw()
})

$('#statusSelectSearch').select2({
  theme: 'bootstrap4',
}).on('change', function () {
  const status = $(this).val() || ''
  addressList
    .columns(9)
    .search(status)
    .draw()
})

$(document).on('click', '#searchBarToggle', function () {
  $('#searchBar').slideToggle(200, function () {
    $('#searchBarToggle').text(($(this).is(':visible') ? 'Hide' : 'Show') + ' filters')
  })
})

let poolList = $('#poolList').DataTable({
  ajax: {
    url: '/pool/roll',
  },
  columns: [
    {
      data: 'name',
      render: (name, type, row) => {
        const color = row.color || '#fff'
        return `${name}<span class="pool-color" style="background: ${color};"></span>`
      }
    },
    { data: 'address_count' },
    {
      data: 'mailing_count',
      render: (count, type, row) => {
        return +count
          ? `<a href="#" data-pool-id="${row.id}" class="mailing-pool-filter">${count}</a>`
          : count
      }
    },
    { data: 'mailing_date' },
    {
      orderable: false,
      targets: 0,
      data: null,
      render: data => {
        return `<button class="btn btn-sm mr-2 btn-primary edit-pool-btn" data-id="${data.id}">Edit</button><button class="btn btn-sm btn-primary add-address-btn" data-id="${data.id}">Add address</button>`
      }
    },
  ],
  paging: true,
  pageLength: 50,
  info: true,
  searching: false,
})

initPoolSelect()

$('#createPoolForm').submit(function (event) {
  event.preventDefault()
  const poolId = $(this).find('input[name="id"]').val()
  let requestUrl = poolId ? `/pool/update/${poolId}` : '/pool/create'
  $.post(requestUrl, $(this).serialize(), res => {
    if (res.success) {
      $(this).trigger('reset')
      $('[data-toggle=tooltip]', this).tooltip('hide')
      poolList.ajax.reload()
      initPoolSelect()
    }
    showAlert(res)
  })
})

$('#createAddressForm').submit(function (event) {
  event.preventDefault()
  const addressId = $(this).find('[name=id]').val()
  const requestUrl = addressId ? `/address/update/${addressId}` : '/address/create'
  $.post(requestUrl, $(this).serialize(), res => {
    if (res.success) {
      if (!addressId) {
        addressList.ajax.reload()
      }
      $('#createAddressFormModal').modal('hide')
    }
    showAlert(res)
  })
})

$(document).on('click', '.add-address-btn', function () {
  $('#poolSelectForm').val($(this).data('id'))
  $('#poolSelectForm').trigger('change')
  $('#createAddressFormModal').modal('show')
})

$(document).on('click', '.edit-pool-btn', function () {
  $.get('/pool/get/' + $(this).data('id'), res => {
    $('#createPoolForm').find('input[name=name]').val(res.name)
    $('#createPoolForm').find('input[name=id]').val(res.id)
    $('#createPoolForm').find('input[name=color]').val(res.color)
    $('#createPoolForm').find('[data-toggle=tooltip]').tooltip('show')
  })
})

$(document).on('click', '.edit-address-btn', function () {
  $.get('/address/get/' + $(this).data('id'), res => {
    $('#company').val(res.company)
    $('#street').val(res.street)
    $('#zip').val(res.zip)
    $('#city').val(res.city)
    $('#country').val(res.country)
    $('#first_name').val(res.firstName)
    $('#last_name').val(res.lastName)
    $('#title').val(res.title)
    $('#position').val(res.position)
    $('#phone').val(res.phone)
    $('#email').val(res.email)
    $('#comment').val(res.comment)
    $('#file_url').val(res.fileUrl)
    $('#var_1').val(res.var1)
    $('#var_2').val(res.var2)
    $('#var_3').val(res.var3)
    $('#var_4').val(res.var4)
    $('#var_5').val(res.var5)
    res.gender && $(`input[name=gender][value=${res.gender}]`).click()
    $('input[name=status]').attr('checked', res.status == 'on')
    $('#poolSelectForm').val(res.pool.id).trigger('change')
    $('#reactionSelect').val(res.reaction.id).trigger('change')
    $('#address_id').val(res.id)
    addressPoolId = res.pool.id
    addressMailingList.ajax.reload()
    $('#blacklistBtn').data('id', res.id)
    $('#createAddressFormModal').modal('show')
  })
})

$('#createAddressFormModal').on('hidden.bs.modal', function () {
  const addressId = $('#address_id').val()
  addressId && updateAddressRow(addressId)
  $('#createAddressForm').trigger('reset')
  $('#poolSelectForm').val(null).trigger('change')
  $('#reactionSelect').val(1).trigger('change')
  $('#blacklistBtn').data('id', '')
  addressPoolId = -1
  addressMailingList.ajax.reload()
  poolList.ajax.reload()
})

$('#selectColForm').on('submit', function (event) {
  event.preventDefault()
  $(this).find('button[type=submit]').attr('disabled', true)
  $(this).find('input[name=file_name]').val(fileName)
  $.post('/import', $(this).serialize(), res => {
    showAlert(res)
    pond.removeFile()
    addressList.ajax.reload()
    poolList.ajax.reload()
    $(this).trigger('reset')
    $('#poolLimitWrap').hide()
    initPoolSelect()
  }, 'json')
    .always(() => {
      $(this).find('button[type=submit]').attr('disabled', false)
      updateGenderApiStats()
    })
})

$('.select-col').each(function () {
  $(this).select2({
    theme: 'bootstrap4',
  })
})

let templateList = $('#templateList').DataTable({
  ajax: {
    url: '/template/roll',
  },
  columns: [
    { data: 'name' },
    { data: 'section' },
    {
      orderable: false,
      targets: 0,
      data: null,
      render: data => {
        return `<button class="btn btn-sm btn-primary edit-template-btn" data-id="${data.id}">Edit</button>`
      }
    },
  ],
  initComplete: function () {
    $('.col-md-6:eq(0)', $(this).DataTable().table().container())
      .append($('<button class="btn btn-primary mb-2 mr-2" data-toggle="modal" data-target="#createTemplateFormModal">Add template</button>'))
  },
  paging: true,
  pageLength: 50,
  info: true,
  searching: false,
})

let templateCodeMirror = CodeMirror.fromTextArea(template_html, {
  lineNumbers: true,
  mode: 'htmlmixed',
})

$('#createTemplateForm').on('submit', function (event) {
  event.preventDefault()
  const submit = $(this).find('button[type=submit]')
  submit.attr('disabled', true)
  $.post('/template/save', $(this).serialize(), res => {
    if (res.success) {
      templateList.ajax.reload()
      $('#createTemplateFormModal').modal('hide')
      initTemplateSelect()
    }
    showAlert(res)
  }).always(() => {
    submit.attr('disabled', false)
  })
})

$('#createTemplateFormModal').on('shown.bs.modal', function () {
  templateCodeMirror.refresh()
}).on('hidden.bs.modal', function () {
  templateCodeMirror.setValue('')
  $('#createTemplateForm').trigger('reset')
})

$(document).on('click', '.add-template-btn', function () {
  $('#createTemplateFormModal').modal('show')
})

$(document).on('click', '.edit-template-btn', function () {
  $.get('/template/get/' + $(this).data('id'), res => {
    $('#template_name').val(res.name)
    $('#template_section').val(res.section)
    $('#template_id').val(res.id)
    templateCodeMirror.setValue(res.file)
    $('#createTemplateFormModal').modal('show')
  })
})

$(document).on('click', '.preview-template-btn', function () {
  const template = templateCodeMirror.getValue()
  $('#previewTemplateForm').find('input').val(template)
  $('#previewTemplateForm').submit()
})

initTemplateSelect()

$('#mailingForm').on('submit', function (event) {
  event.preventDefault()
  const submit = $('button[type=submit]', this)
  submit.attr('disabled', true)
  $.post('/campaign/create', $(this).serialize(), res => {
    if (res.success) {
      mailingList.ajax.reload()
      poolList.ajax.reload()
    }
    showAlert(res)
  }).always(() => {
    submit.attr('disabled', false)
  })
})

let mailingPoolId = ''

let mailingList = $('#mailingList').DataTable({
  ajax: {
    url: '/campaign/roll',
    type: 'POST',
    data: data => {
      data.pool = mailingPoolId
      return data
    },
  },
  columns: [
    { data: 'id' },
    { data: 'pool' },
    { data: 'template' },
    { data: 'section' },
    { data: 'date' },
    {
      orderable: false,
      targets: 0,
      data: null,
      render: data => {
        return `<a href="/uploads/${data.file}">${data.file}</a>`
      }
    }
  ],
  initComplete: function () {
    $('.col-md-6:eq(1)', $(this).DataTable().table().container())
      .append($('<button class="btn btn-primary float-right mb-2 mailing-pool-all">All pools</button>'))
  },
  order: [[0, 'desc']],
  paging: true,
  pageLength: 50,
  info: true,
  searching: false,
})

let addressPoolId = -1
let addressMailingList = $('#addressMailingList').DataTable({
  ajax: {
    url: '/campaign/roll',
    type: 'POST',
    data: data => {
      data.pool = addressPoolId
      return data
    },
  },
  columns: [
    { data: 'template' },
    { data: 'section' },
    { data: 'date' },
    {
      orderable: false,
      targets: 0,
      data: null,
      render: data => {
        return `<a href="/uploads/${data.file}">${data.file}</a>`
      }
    }
  ],
  order: [[0, 'desc']],
  paging: false,
  info: false,
  searching: false,
})

let fileName
const pond = FilePond.create($('input[name=file]')[0], {
  server: {
    process: {
      url: './import/upload',
      onload: res => {
        const data = JSON.parse(res)
        fileName = data.text
        getImportAmount()
        getImportTitles()
      }
    },
  },
  onremovefile: () => {
    $('.select-col').each(function () {
      $(this).empty()
    })
  },
})

initReactionSelect()

$(document).on('click', '.mailing-pool-filter', function (event) {
  event.preventDefault()
  mailingPoolId = $(this).data('pool-id')
  mailingList.ajax.reload()
  $('#navMailingsTab').click()
})

$(document).on('click', '.mailing-pool-all', function () {
  mailingPoolId = ''
  mailingList.ajax.reload()
})

$(document).on('click', '#blacklistBtn', function (event) {
  event.preventDefault()
  if (!confirm('Blacklist this address?')) {
    return
  }

  const addressId = $(this).data('id')
  $.get('/address/add_to_blacklist/' + addressId, () => {
    $('#createAddressFormModal').modal('hide')
    addressList
      .row($('#row_' + addressId))
      .remove()
      .draw()
    blackList.ajax.reload()
  })
})

$(document).on('click', '#deleteAddress', function (event) {
  event.preventDefault()
  if (!confirm('Delete this address?')) {
    return
  }

  const addressId = $('#address_id').val()
  $.get('/address/delete/' + addressId, () => {
    $('#createAddressFormModal').modal('hide')
    addressList
      .row($('#row_' + addressId))
      .remove()
      .draw()
  })
})

$(document).on('click', '#addressCreateMailing', function (event) {
  event.preventDefault()
  $(this).attr('disabled', true)
  const template = $('[name=address_mailing_template]').val()
  const date = $('[name=address_mailing_date]').val()
  const address_id = $('#address_id').val()
  $.post('/campaign/create', {
    template,
    date,
    address_id,
    pool: -1,
  }, res => {
    if (res.success) {
      mailingList.ajax.reload()
      $('#createAddressFormModal').modal('hide')
    }
    showAlert(res)
  }).always(() => {
    $(this).attr('disabled', false)
  })
})

let blackList = $('#blackList').DataTable({
  ajax: {
    url: '/address/blacklist',
    type: 'POST',
  },
  columns: [
    { data: 'company' },
    { data: 'street' },
    { data: 'zip' },
    { data: 'city' },
    { data: 'country' },
    { data: 'gender' },
    { data: 'firstName' },
    { data: 'lastName' },
    { data: 'phone' },
  ],
  paging: true,
  pageLength: 50,
  info: true,
  searching: true,
})

let actionList = $('#actionList').DataTable({
  ajax: {
    url: '/reaction/roll',
  },
  columns: [
    { data: 'id' },
    { data: 'name' },
  ],
  paging: false,
  info: false,
  searching: false,
})

$('#createActionForm').on('submit', function (event) {
  event.preventDefault()
  $.post('/reaction/create', $(this).serialize(), res => {
    if (res.success) {
      $(this).trigger('reset')
      actionList.ajax.reload()
      initReactionSelect()
    }
    showAlert(res)
  })
})

$('#duplicatesSelect')
  .select2({
    theme: 'bootstrap4',
    allowClear: true,
    placeholder: 'Search duplicates',
  })
  .on('change', function () {
    const lastValue = $(this).val().pop()
    addressList.ajax.reload()
    if (lastValue) {
      const colIndex = addressList.column(lastValue + ':name').index()
      addressList.order([colIndex, 'asc']).draw()
    }
  })

function getImportTitles () {
  $.post('/import/titles', { fileName }, res => {
    if (res.success) {
      $('.select-col').each(function () {
        $(this).select2({
          data: res.text,
          theme: 'bootstrap4',
        })
      })
    } else {
      showAlert(res)
    }
  })
}

function getImportAmount () {
  $.post('/import/amount', { fileName }, res => {
    showAlert(res)
  })
}

function initPoolSelect () {
  $.get('/pool/roll', res => {
    const data = res.data.map(item => {
      return {
        id: item.id,
        text: item.name,
      }
    })

    $('#poolSelect').empty()
    $('#poolSelect')
      .select2({
        data,
        multiple: true,
        theme: 'bootstrap4',
        placeholder: 'Filter',
      })
      .on('change', () => addressList.ajax.reload())

    $('#poolSelectForm').empty()
    $('#poolSelectForm').select2({
      data,
      theme: 'bootstrap4',
      placeholder: 'Pool',
    })

    $('#poolSelectMailing').empty()
    $('#poolSelectMailing').select2({
      data: [
        {
          id: '',
          text: '',
          selected: true,
          disabled: true,
        },
        ...data,
      ],
      theme: 'bootstrap4',
      placeholder: 'Pool',
    })

    $('#poolSelectImport').empty()
    $('#poolSelectImport').select2({
      data: [
        {
          id: '',
          text: '',
          selected: true,
          disabled: true,
        },
        {
          id: -1,
          text: 'Create new pool',
        },
        {
          id: 0,
          text: 'Auto - creating pool(s)',
        },
        {
          id: -2,
          text: 'Split to pools'
        },
        ...data,
      ],
      theme: 'bootstrap4',
      placeholder: 'Add to pool',
    }).on('change', function () {
      if ($(this).val() == -2) {
        $('#poolLimitWrap').show()
      } else {
        $('#poolLimitWrap').hide()
      }
    })
  })
}

function initTemplateSelect () {
  $.get('/template/roll', res => {
    const data = [{
      id: '',
      text: '',
      selected: true,
      disabled: true,
    }, ...res.data.map(item => {
      return {
        id: item.id,
        text: `${item.name} (${item.section})`,
      }
    })]

    $('.template-select-mailing').each(function () {
      $(this).empty()
      $(this).select2({
        data,
        theme: 'bootstrap4',
        placeholder: 'Template',
      })
    })
  })
}

function showAlert (res) {
  const alert = $(`<div class="alert alert-${res.success ? 'success' : 'danger'} fade show" role="alert">${res.text}</div>`)
  alert.prependTo($('.container-fluid'))
  setTimeout(() => {
    alert.alert('close')
  }, 2500)
}

function updateAddressRow (addressId) {
  $.get('/address/get/' + addressId, res => {
    const selector = '#row_' + addressId
    addressList.row(selector).data(res)
    $(selector).css('background', res.pool.color || 'white')
    if (+res.reaction.id > 1) {
      $(selector).addClass('bg-green')
    } else {
      $(selector).removeClass('bg-green')
    }
  })
}

function initReactionSelect () {
  $.get('/reaction/roll', res => {
    $('#reactionSelect').empty()
    $('#reactionSelect').select2({
      data: res.data.map(item => {
        return {
          id: item.id,
          text: item.name,
        }
      }),
      theme: 'bootstrap4',
    })

    $('#reactionSelectSearch').empty()
    $('#reactionSelectSearch').select2({
      data: [
        {
          id: '',
          text: '',
        },
        ...res.data.map(item => {
          return {
            id: item.id,
            text: item.name,
          }
        })
      ],
      theme: 'bootstrap4',
    })
  })
}

function updateGenderApiStats () {
  $.get('/import/gender_api_stats', res => {
    if (res.success) {
      $('#genderApiStats').text('remaining requests: ' + res.text.remaining_requests)
    } else {
      showAlert(res)
    }
  })
}

updateGenderApiStats()