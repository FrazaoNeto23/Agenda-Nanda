document.addEventListener('DOMContentLoaded', function () {
  let calendarEl = document.getElementById('calendar');
  let modal = document.getElementById('modal-agenda');
  let closeModal = modal ? modal.querySelector('.close') : null;
  let formAgenda = document.getElementById('form-agenda');
  let selectService = document.getElementById('agenda-service');
  let inputDate = document.getElementById('agenda-date');

  // 📅 Configuração do calendário
  let calendar = new FullCalendar.Calendar(calendarEl, {
    locale: 'pt-br',
    initialView: 'dayGridMonth',
    selectable: true,
    editable: false,
    height: 'auto',
    headerToolbar: {
      left: 'prev,next today',
      center: 'title',
      right: 'dayGridMonth,timeGridWeek,timeGridDay'
    },
    buttonText: {
      today: 'Hoje',
      month: 'Mês',
      week: 'Semana',
      day: 'Dia'
    },
    events: 'get_events.php',

    // 📅 Define classes CSS de acordo com o status do evento
    eventClassNames: function (arg) {
      let status = arg.event.extendedProps.status;
      if (status === 'atendido' || status === 'concluido') {
        return ['concluido'];
      } else if (status === 'cancelado') {
        return ['cancelado'];
      } else {
        return ['agendado'];
      }
    },

    // 📅 Quando seleciona uma data abre modal
    select: function (info) {
      if (modal) {
        // Define a data selecionada no input
        inputDate.value = info.startStr;
        modal.style.display = 'block';
      }
    },

    // 📅 Clique no evento → alterar status
    eventClick: function (info) {
      let statusAtual = info.event.extendedProps.status;
      let eventId = info.event.id;

      if (statusAtual === 'agendado') {
        if (confirm("Deseja marcar este agendamento como concluído?")) {
          updateStatus(eventId, 'concluido');
        }
      } else if (statusAtual === 'concluido') {
        if (confirm("Deseja cancelar este agendamento?")) {
          updateStatus(eventId, 'cancelado');
        }
      } else if (statusAtual === 'cancelado') {
        if (confirm("Deseja reabrir este agendamento como AGENDADO?")) {
          updateStatus(eventId, 'agendado');
        }
      }
    }
  });

  calendar.render();

  // 🔄 Função para atualizar status
  function updateStatus(id, status) {
    fetch('update_status.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: `id=${id}&status=${status}`
    })
      .then(res => res.json())
      .then(data => {
        if (data.status === 'success') {
          alert(data.msg);
          calendar.refetchEvents();
        } else {
          alert('Erro: ' + data.msg);
        }
      })
      .catch(err => {
        console.error('Erro:', err);
        alert('Erro ao atualizar status');
      });
  }

  // 📅 Fecha modal
  if (closeModal) {
    closeModal.onclick = function () {
      modal.style.display = 'none';
    }

    window.onclick = function (event) {
      if (event.target == modal) {
        modal.style.display = 'none';
      }
    }
  }

  // 📅 Submissão do agendamento
  if (formAgenda) {
    formAgenda.addEventListener('submit', function (e) {
      e.preventDefault();

      const serviceId = selectService.value;
      const date = inputDate.value;
      const time = document.getElementById('agenda-time').value;
      const endTime = document.getElementById('agenda-end-time').value;

      // Validação básica
      if (!serviceId || !date || !time) {
        alert('Por favor, preencha todos os campos obrigatórios!');
        return;
      }

      // Envia o formulário
      fetch('add_from_calendar.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `service_id=${serviceId}&date=${date}&time=${time}&end_time=${endTime}`
      })
        .then(res => res.json())
        .then(resp => {
          if (resp.status === 'success') {
            alert(resp.msg);
            calendar.refetchEvents();
            modal.style.display = 'none';
            formAgenda.reset();
          } else {
            alert('Erro: ' + resp.msg);
          }
        })
        .catch(err => {
          console.error('Erro:', err);
          alert('Erro ao criar agendamento');
        });
    });
  }
});