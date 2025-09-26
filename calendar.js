document.addEventListener('DOMContentLoaded', function () {
  let calendarEl = document.getElementById('calendar');
  let modal = document.getElementById('modal-agenda');
  let closeModal = modal ? modal.querySelector('.close') : null;
  let formAgenda = document.getElementById('form-agenda');
  let selectService = document.getElementById('agenda-service');
  let inputDate = document.getElementById('agenda-date');

  if (selectService) {
    fetch('get_services.php')
      .then(res => res.json())
      .then(data => {
        selectService.innerHTML = data.map(s => `<option value="${s.id}">${s.name}</option>`).join('');
      });
  }

  let calendar = new FullCalendar.Calendar(calendarEl, {
    initialView: 'dayGridMonth',
    selectable: true,
    editable: true,
    height: 'auto',
    events: 'get_events.php',
    eventClassNames: function (arg) {
      return arg.event.extendedProps.status === 'atendido' ? ['event-done'] : ['event-agendado'];
    },
    select: function (info) {
      if (modal) {
        inputDate.value = info.startStr;
        modal.style.display = 'block';
      }
    },
    eventClick: function (info) {
      if (confirm("Deseja marcar este agendamento como atendido?")) {
        fetch('update_status.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: `id=${info.event.id}&status=atendido`
        }).then(() => calendar.refetchEvents());
      }
    }
  });

  calendar.render();

  if (closeModal) {
    closeModal.onclick = function () { modal.style.display = 'none'; }
    window.onclick = function (event) { if (event.target == modal) modal.style.display = 'none'; }
  }

  if (formAgenda) {
    formAgenda.addEventListener('submit', function (e) {
      e.preventDefault();
      fetch('add_from_calendar.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `service_id=${selectService.value}&date=${inputDate.value}&time=${document.getElementById('agenda-time').value}&end_time=${document.getElementById('agenda-end-time').value}`
      }).then(res => res.json())
        .then(resp => {
          if (resp.status === 'success') {
            alert('Agendamento realizado!');
            calendar.refetchEvents();
            modal.style.display = 'none';
          } else { alert(resp.msg); }
        });
    });
  }
});
