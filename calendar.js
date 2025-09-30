document.addEventListener('DOMContentLoaded', function () {
  let calendarEl = document.getElementById('calendar');
  let modal = document.getElementById('modal-agenda');
  let closeModal = modal ? modal.querySelector('.close') : null;
  let formAgenda = document.getElementById('form-agenda');
  let selectService = document.getElementById('agenda-service');
  let inputDate = document.getElementById('agenda-date');

  // 🔽 Carrega os serviços no select
  if (selectService) {
    fetch('get_services.php')
      .then(res => res.json())
      .then(data => {
        selectService.innerHTML = data
          .map(s => `<option value="${s.id}">${s.name}</option>`)
          .join('');
      });
  }

  // 📅 Configuração do calendário
  let calendar = new FullCalendar.Calendar(calendarEl, {
    initialView: 'dayGridMonth',
    selectable: true,
    editable: true,
    height: 'auto',
    events: 'get_events.php',

    // 🔽 Define classes CSS de acordo com o status do evento
    eventClassNames: function (arg) {
      let status = arg.event.extendedProps.status;
      if (status === 'atendido' || status === 'concluido') {
        return ['concluido']; // ✅ concluído
      } else if (status === 'cancelado') {
        return ['cancelado']; // ❌ cancelado
      } else {
        return ['agendado']; // 📅 agendado
      }
    },

    // 🔽 Quando seleciona uma data abre modal
    select: function (info) {
      if (modal) {
        inputDate.value = info.startStr;
        modal.style.display = 'block';
      }
    },

    // 🔽 Clique no evento → alterar status
    eventClick: function (info) {
      let statusAtual = info.event.extendedProps.status;

      if (statusAtual === 'agendado') {
        if (confirm("Deseja marcar este agendamento como concluído?")) {
          fetch('update_status.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `id=${info.event.id}&status=concluido`
          }).then(() => calendar.refetchEvents());
        }
      } else if (statusAtual === 'concluido') {
        if (confirm("Deseja cancelar este agendamento?")) {
          fetch('update_status.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `id=${info.event.id}&status=cancelado`
          }).then(() => calendar.refetchEvents());
        }
      } else if (statusAtual === 'cancelado') {
        if (confirm("Deseja reabrir este agendamento como AGENDADO?")) {
          fetch('update_status.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `id=${info.event.id}&status=agendado`
          }).then(() => calendar.refetchEvents());
        }
      }
    }
  });

  calendar.render();

  // 🔽 Fecha modal
  if (closeModal) {
    closeModal.onclick = function () { modal.style.display = 'none'; }
    window.onclick = function (event) { if (event.target == modal) modal.style.display = 'none'; }
  }

  // 🔽 Submissão do agendamento
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
          } else {
            alert(resp.msg);
          }
        });
    });
  }
});
