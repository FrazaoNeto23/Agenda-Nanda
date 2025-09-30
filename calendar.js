document.addEventListener('DOMContentLoaded', function () {
  let calendarEl = document.getElementById('calendar');
  let modal = document.getElementById('modal-agenda');
  let closeModal = modal ? modal.querySelector('.close') : null;
  let formAgenda = document.getElementById('form-agenda');
  let selectService = document.getElementById('agenda-service');
  let inputDate = document.getElementById('agenda-date');
  let inputTime = document.getElementById('agenda-time');
  let inputEndTime = document.getElementById('agenda-end-time');

  // Debug: Verificar se os elementos existem
  console.log('Modal:', modal);
  console.log('Form:', formAgenda);
  console.log('Select Service:', selectService);

  // Carregar serviços
  if (selectService) {
    fetch('get_services.php')
      .then(res => res.json())
      .then(data => {
        console.log('Serviços carregados:', data);
        selectService.innerHTML = '<option value="">Selecione um serviço</option>' +
          data.map(s => `<option value="${s.id}">${s.name} - R$ ${parseFloat(s.price).toFixed(2)}</option>`).join('');
      })
      .catch(err => {
        console.error('Erro ao carregar serviços:', err);
        selectService.innerHTML = '<option value="">Erro ao carregar serviços</option>';
      });
  }

  // Configurar horário fim automaticamente (1 hora após início)
  if (inputTime && inputEndTime) {
    inputTime.addEventListener('change', function () {
      if (this.value) {
        let [hours, minutes] = this.value.split(':');
        let endHour = (parseInt(hours) + 1) % 24;
        inputEndTime.value = `${String(endHour).padStart(2, '0')}:${minutes}`;
      }
    });
  }

  // Inicializar calendário
  let calendar = new FullCalendar.Calendar(calendarEl, {
    initialView: 'dayGridMonth',
    locale: 'pt-br',
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
    eventClassNames: function (arg) {
      return arg.event.extendedProps.status === 'atendido' ? ['event-done'] : ['event-agendado'];
    },
    eventTimeFormat: {
      hour: '2-digit',
      minute: '2-digit',
      hour12: false
    },
    select: function (info) {
      if (modal) {
        // Formatar data para o input
        let selectedDate = info.startStr.split('T')[0];
        inputDate.value = selectedDate;

        // Definir horário padrão (9:00 - 10:00)
        inputTime.value = '09:00';
        inputEndTime.value = '10:00';

        modal.style.display = 'block';
      }
      calendar.unselect();
    },
    eventClick: function (info) {
      if (confirm("Deseja marcar este agendamento como atendido?")) {
        fetch('update_status.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: `id=${info.event.id}&status=atendido`
        })
          .then(res => res.text())
          .then(() => {
            calendar.refetchEvents();
            alert('Status atualizado com sucesso!');
          })
          .catch(err => console.error('Erro ao atualizar status:', err));
      }
    }
  });

  calendar.render();

  // Fechar modal
  if (closeModal) {
    closeModal.onclick = function () { modal.style.display = 'none'; }
    window.onclick = function (event) {
      if (event.target == modal) modal.style.display = 'none';
    }
  }

  // Submeter agendamento
  if (formAgenda) {
    formAgenda.addEventListener('submit', function (e) {
      e.preventDefault();

      if (!selectService.value) {
        alert('Por favor, selecione um serviço!');
        return;
      }

      fetch('add_from_calendar.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `service_id=${selectService.value}&date=${inputDate.value}&time=${inputTime.value}&end_time=${inputEndTime.value}`
      })
        .then(res => res.json())
        .then(resp => {
          if (resp.status === 'success') {
            alert('✅ Agendamento realizado com sucesso!');
            calendar.refetchEvents();
            modal.style.display = 'none';
            formAgenda.reset();
          } else {
            alert('❌ ' + resp.msg);
          }
        })
        .catch(err => {
          console.error('Erro:', err);
          alert('Erro ao realizar agendamento. Tente novamente.');
        });
    });
  }
});