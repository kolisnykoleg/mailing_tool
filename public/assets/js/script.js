$.fn.dataTable.ext.errMode = 'none'
console.log('OK')

let addressList = $('#addressList').DataTable({
  ajax: {
    url: '/address/list',
    type: 'POST',
    dataSrc: '',
    data: data => {
      data.pools = $('#poolSelect').val()
      data.duplicates = $('#duplicatesSelect').val()
      return data
    },
  },
  columns: [
    { data: 'company', name: 'company' },
    { data: 'street', name: 'street' },
    { data: 'zip', name: 'zip' },
    { data: 'city', name: 'city' },
    { data: 'country', name: 'country' },
    { data: 'gender', name: 'gender' },
    { data: 'firstName', name: 'first_name' },
    { data: 'lastName', name: 'last_name' },
    { data: 'phone', name: 'phone' },
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
  initComplete: function () {
    $('.col-md-6:eq(0)', $(this).DataTable().table().container())
      .append($('<button class="btn btn-primary mb-2 mr-2" data-toggle="modal" data-target="#createAddressFormModal">Add address</button>'))
      .append($('<button class="btn btn-primary mb-2 mr-2" id="searchBarToggle">Show filters</button>'))
      .append($('<button class="btn btn-primary mb-2 mr-2" id="clearSearch">Clear filters</button>'))
      .append($('<form action="/address/export" method="post" class="d-inline" id="export"><button type="submit" class="btn btn-primary mb-2 mr-2">Export</button></form>'))
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
  lengthMenu: [[50, 250, 500, -1], [50, 250, 500, 'All']],
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

$(document).on('submit', '#export', function () {
  const ids = addressList.rows().data().toArray().map(data => data.id).join(',')
  $(this).append(`<input type="hidden" name="ids" value="${ids}">`)
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
    url: '/pool/list',
    type: 'POST',
    dataSrc: '',
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
    $(this).trigger('reset')
    $('[data-toggle=tooltip]', this).tooltip('hide')
    poolList.ajax.reload()
    initPoolSelect()
    showAlert(res, true)
  })
    .fail(ajaxFail)
})

$('#createAddressForm').submit(function (event) {
  event.preventDefault()
  const addressId = $(this).find('[name=id]').val()
  const requestUrl = addressId ? `/address/update/${addressId}` : '/address/create'
  $.post(requestUrl, $(this).serialize(), res => {
    if (!addressId) {
      addressList.ajax.reload()
    }
    $('#createAddressFormModal').modal('hide')
    showAlert(res, true)
  })
    .fail(ajaxFail)
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
    $('#createPoolForm').find('input[name=color]').val(res.color || '#ffffff')
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
    $('input[name=status]').attr('checked', res.status)
    $('#poolSelectForm').val(res.pool.id).trigger('change')
    $('#reactionSelect').val(res.reaction.id).trigger('change')
    $('#address_id').val(res.id)
    addressMailingList.ajax.url(`/campaign/list/address/${res.id}`).load()
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
  addressMailingList.clear().draw()
  poolList.ajax.reload()
})
  .on('show.bs.modal', function () {
    addressMailingList.clear().draw()
  })

$('#selectColForm').on('submit', function (event) {
  event.preventDefault()
  $(this).find('button[type=submit]').attr('disabled', true)
  $(this).find('input[name=file_name]').val(fileName)
  $.post('/import', $(this).serialize(), res => {
    showAlert(res, true)
    pond.removeFile()
    addressList.ajax.reload()
    poolList.ajax.reload()
    $(this).trigger('reset')
    $('#poolLimitWrap').hide()
    initPoolSelect()
  }, 'json')
    .fail(ajaxFail)
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
    url: '/template/list',
    type: 'POST',
    dataSrc: '',
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
  const templateId = $('#template_id').val()
  const requestUrl = templateId ? `/template/update/${templateId}` : '/template/create'
  $.post(requestUrl, $(this).serialize(), res => {
    templateList.ajax.reload()
    $('#createTemplateFormModal').modal('hide')
    initTemplateSelect()
    showAlert(res, true)
  })
    .fail(ajaxFail)
    .always(() => {
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
    templateCodeMirror.setValue(res.content)
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
    mailingList.ajax.reload()
    poolList.ajax.reload()
    showAlert(res, true)
  })
    .fail(ajaxFail)
    .always(() => {
      submit.attr('disabled', false)
    })
})

let mailingList = $('#mailingList').DataTable({
  ajax: {
    url: '/campaign/list',
    type: 'POST',
    dataSrc: '',
  },
  columns: [
    {
      data: 'id',
      visible: false,
    },
    {
      data: null,
      render: data => {
        return data && data.pool && data.pool.name
      }
    },
    { data: 'template.name' },
    { data: 'template.section' },
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

let addressMailingList = $('#addressMailingList').DataTable({
  ajax: {
    url: '/campaign/list',
    type: 'POST',
    dataSrc: '',
  },
  columns: [
    { data: 'template.name' },
    { data: 'template.section' },
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
  const poolId = $(this).data('pool-id')
  mailingList.ajax.url(`/campaign/list/pool/${poolId}`).load()
  mailingList.ajax.url('/campaign/list')
  $('#navMailingsTab').click()
})

$(document).on('click', '.mailing-pool-all', function () {
  mailingList.ajax.reload()
})

$(document).on('click', '#blacklistBtn', function (event) {
  event.preventDefault()
  if (!confirm('Blacklist this address?')) {
    return
  }

  const addressId = $(this).data('id')
  $.get('/address/add-to-blacklist/' + addressId, res => {
    showAlert(res, true)
    $('#createAddressFormModal').modal('hide')
    $('#row_' + addressId).remove()
    blackList.ajax.reload()
  })
    .fail(ajaxFail)
})

$(document).on('click', '#deleteAddress', function (event) {
  event.preventDefault()
  if (!confirm('Delete this address?')) {
    return
  }

  const addressId = $('#address_id').val()
  $.get('/address/delete/' + addressId, res => {
    showAlert(res, true)
    $('#createAddressFormModal').modal('hide')
    $('#row_' + addressId).remove()
  })
    .fail(ajaxFail)
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
    mailingList.ajax.reload()
    $('#createAddressFormModal').modal('hide')
    showAlert(res, true)
  })
    .fail(ajaxFail)
    .always(() => {
      $(this).attr('disabled', false)
    })
})

let blackList = $('#blackList').DataTable({
  ajax: {
    url: '/address/blacklist',
    type: 'POST',
    dataSrc: '',
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
    url: '/reaction/list',
    type: 'POST',
    dataSrc: '',
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
    $(this).trigger('reset')
    actionList.ajax.reload()
    initReactionSelect()
    showAlert(res, true)
  })
    .fail(ajaxFail)
})

$('#duplicatesSelect')
  .select2({
    theme: 'bootstrap4',
    allowClear: true,
    placeholder: 'Search duplicates',
  })
  .on('change', function () {
    const lastValue = $(this).val().pop()
    $('.spinner-wrap').show()
    addressList.ajax.reload(() => {
      $('.spinner-wrap').hide()
    })
    if (lastValue) {
      const colIndex = addressList.column(lastValue + ':name').index()
      colIndex && addressList.order([colIndex, 'asc']).draw()
    }
  })

function getImportTitles () {
  $.post('/import/titles', { fileName }, res => {
    $('.select-col').each(function () {
      $(this).select2({
        data: [
          {
            id: '',
            text: '',
            selected: true,
            disabled: true,
          },
          ...res,
        ],
        theme: 'bootstrap4',
      })
    })
  })
}

function getImportAmount () {
  $.post('/import/amount', { fileName }, res => {
    showAlert(res, true)
  })
    .fail(ajaxFail)
}

function initPoolSelect () {
  $.get('/pool/list', res => {
    const data = res.map(item => {
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
      .on('change', () => {
        $('.spinner-wrap').show()
        addressList.ajax.reload(() => {
          $('.spinner-wrap').hide()
        })
      })

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
  $.get('/template/list', res => {
    const data = [{
      id: '',
      text: '',
      selected: true,
      disabled: true,
    }, ...res.map(item => {
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

function showAlert (res, success) {
  const alert = $(`<div class="alert alert-${success ? 'success' : 'danger'} fade show" role="alert">${res.text}</div>`)
  alert.prependTo($('.container-fluid'))
  setTimeout(() => {
    alert.alert('close')
  }, 3000)
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
  $.get('/reaction/list', res => {
    $('#reactionSelect').empty()
    $('#reactionSelect').select2({
      data: res.map(item => {
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
        ...res.map(item => {
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
  $.get('/import/gender-api-stats', res => {
    $('#genderApiStats').text('remaining requests: ' + res.text.remaining_requests)
  })
    .fail(ajaxFail)
}

function ajaxFail (xhr) {
  showAlert(xhr.responseJSON, false)
}

updateGenderApiStats()