$(function () {
  $('[data-table="true"]').DataTable({
    responsive: true,
    pageLength: 10,
    dom: 'Bfrtip',
    buttons: ['copy', 'csv', 'excel', 'print'],
    language: window.ERP_LANG === 'bn'
      ? { search: 'অনুসন্ধান:', lengthMenu: '_MENU_ দেখান', info: '_TOTAL_টির মধ্যে _START_ থেকে _END_', paginate: { next: 'পরবর্তী', previous: 'আগের' }, zeroRecords: 'কোনো রেকর্ড নেই', emptyTable: 'কোনো রেকর্ড নেই' }
      : { emptyTable: 'No records found' },
  });

  $('[data-confirm]').on('submit', function (event) {
    event.preventDefault();
    const form = this;
    Swal.fire({
      text: $(form).data('confirm'),
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: $(form).data('confirm-button') || 'OK'
    }).then((result) => {
      if (result.isConfirmed) {
        form.submit();
      }
    });
  });

  $('.mobile-nav-toggle').on('click', function () {
    $('.sidebar').toggleClass('open');
  });

  const sidebar = $('.sidebar');
  const savedSidebarScroll = sessionStorage.getItem('erpSidebarScroll');
  if (savedSidebarScroll) {
    sidebar.scrollTop(parseInt(savedSidebarScroll, 10) || 0);
  }
  sidebar.on('scroll', function () {
    sessionStorage.setItem('erpSidebarScroll', String(this.scrollTop));
  });
  $('.sidebar a').each(function () {
    const link = new URL(this.href, window.location.origin);
    if (window.location.pathname === link.pathname || window.location.pathname.startsWith(link.pathname + '/')) {
      $(this).addClass('active');
    }
  });

  $('[data-recipient-type]').on('change', function () {
    const value = this.value;
    $('[data-recipient-panel]').toggleClass('d-none', true);
    $('[data-recipient-panel="' + value + '"]').toggleClass('d-none', false);
  }).trigger('change');

  $('[data-dynamic-total]').on('input', function () {
    const group = $(this).closest('[data-total-group]');
    const total = group.find('[data-dynamic-total]').toArray().reduce((sum, input) => sum + (parseFloat(input.value) || 0), 0);
    group.find('[data-total-output]').text(total.toFixed(2));
  });

  $(document).on('click', '.password-toggle', function () {
    const button = $(this);
    const span = button.find('span');
    const showing = button.data('showing') === true;
    span.text(showing ? button.data('hidden') : button.data('secret'));
    button.find('i').toggleClass('fa-eye fa-eye-slash');
    button.data('showing', !showing);
  });
});
