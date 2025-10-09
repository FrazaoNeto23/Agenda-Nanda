document.addEventListener('DOMContentLoaded', function () {
  let calendarEl = document.getElementById('calendar');
  let modal = document.getElementById('modal-agenda');
  let closeModal = modal ? modal.querySelector('.close') : null;
  let formAgenda = document.getElementById('form-agenda');
  let selectService = document.getElementById('agenda-service');
  let inputDate = document.getElementById('agenda-date');
  let inputTime = document.getElementById('agenda-time');
  let inputEndTime = document.getElementById('agenda-end-time');

  // Função auxiliar para mostrar mensagens
  function showMessage(message, type = 'info') {
    const existingMsg = document.querySelector('.toast-message');
    if (existingMsg) existingMsg.remove();

    const toast = document.createElement('div');
    toast.className = `toast-message toast-${type}`;
    toast.textContent = message;
    document.body.appendChild(toast);

    setTimeout(() => toast.classList.add('show'), 10);
    setTimeout(() => {
      toast.classList.remove('show');
      setTimeout(() => toast.remove(), 300);
    }, 3000);
  }

  // Carregar serviços
  if (selectService) {
    selectService.innerHTML = '<option value="">Carregando serviços...</option>';

    fetch('get_services.php')
      .then(res => {
        if (!res.ok) throw new Error('Erro ao carregar serviços');
        return res.json();
      })
      .then(data => {
        if (data.length === 0) {
          selectService.innerHTML = '<option value="">Nenhum serviço disponível</option>';
          showMessage('Nenhum serviço encontrado. Contate o administrador.', 'warning');
        } else {
          selectService.innerHTML = '<option value="">Selecione um serviço</option>' +
            data.map(s => `<option value="${s.id}" data-duration="${s.duration || 60}">${s.name} - R$ ${parseFloat(s.price).toFixed(2)}</option>`).join('');
        }
      })
      .catch(err => {
        console.error('Erro ao carregar serviços:', err);
        selectService.innerHTML = '<option value="">Erro ao carregar serviços</option>';
        showMessage('Erro ao carregar serviços. Recarregue a página.', 'error');
      });
  }

  // Configurar horário fim baseado na duração do serviço
  if (selectService && inputTime && inputEndTime) {
    function updateEndTime() {
      const selectedOption = selectService.options[selectService.selectedIndex];
      const duration = parseInt(selectedOption.getAttribute('data-duration')) || 60;

      if (inputTime.value) {
        const [hours, minutes] = inputTime.value.split(':').map(Number);
        const totalMinutes = hours * 60 + minutes + duration;
        const endHour = Math.floor(totalMinutes / 60) % 24;
        const endMin = totalMinutes % 60;
        inputEndTime.value = `${String(endHour).padStart(2, '0')}:${String(endMin).padStart(2, '0')}`;
      }
    }

    selectService.addEventListener('change', updateEndTime);
    inputTime.addEventListener('change', updateEndTime);
  }

  // Validar data (não permitir datas passadas)
  if (inputDate) {
    const today = new Date().toISOString().split('T')[0];
    inputDate.setAttribute('min', today);
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
    events: function (fetchInfo, successCallback, failureCallback) {
      fetch('get_events.php')
        .then(res => {
          if (!res.ok) throw new Error('Erro ao carregar eventos');
          return res.json();
        })
        .then(data => successCallback(data))
        .catch(err => {
          console.error('Erro ao carregar eventos:', err);
          failureCallback(err);
          showMessage('Erro ao carregar agendamentos', 'error');
        });
    },
    eventClassNames: function (arg) {
      const status = arg.event.extendedProps.status || 'agendado';
      return status === 'concluido' ? ['event-done'] : ['event-agendado'];
    },
    eventTimeFormat: {
      hour: '2-digit',
      minute: '2-digit',
      hour12: false
    },
    select: function (info) {
      if (modal) {
        // Verificar se a data selecionada não é no passado
        const selectedDate = new Date(info.startStr);
        const today = new Date();
        today.setHours(0, 0, 0, 0);

        if (selectedDate < today) {
          showMessage('Não é possível agendar em datas passadas', 'warning');
          calendar.unselect();
          return;
        }

        // Formatar data para o input
        let dateStr = info.startStr.split('T')[0];
        inputDate.value = dateStr;

        // Definir horário padrão (9:00 - 10:00)
        inputTime.value = '09:00';
        inputEndTime.value = '10:00';

        // Limpar seleção de serviço
        if (selectService) selectService.value = '';

        modal.style.display = 'block';
      }
      calendar.unselect();
    },
    eventClick: function (info) {
      const eventStatus = info.event.extendedProps.status || 'agendado';

      if (eventStatus === 'concluido') {
        showMessage('Este agendamento já foi concluído', 'info');
        return;
      }

      if (confirm("Deseja marcar este agendamento como concluído?")) {
        fetch('update_status.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: `id=${info.event.id}&status=concluido`
        })
          .then(res => res.json())
          .then(data => {
            if (data.status === 'success') {
              calendar.refetchEvents();
              showMessage('✅ Agendamento marcado como concluído!', 'success');
            } else {
              showMessage('❌ ' + (data.msg || 'Erro ao atualizar status'), 'error');
            }
          })
          .catch(err => {
            console.error('Erro ao atualizar status:', err);
            showMessage('Erro ao atualizar status. Tente novamente.', 'error');
          });
      }
    }
  });

  calendar.render();

  // Fechar modal
  if (closeModal) {
    closeModal.onclick = function () {
      modal.style.display = 'none';
      formAgenda.reset();
    }

    window.onclick = function (event) {
      if (event.target == modal) {
        modal.style.display = 'none';
        formAgenda.reset();
      }
    }
  }

  // Submeter agendamento
  if (formAgenda) {
    formAgenda.addEventListener('submit', function (e) {
      e.preventDefault();

      // Validações
      if (!selectService.value) {
        showMessage('⚠️ Por favor, selecione um serviço!', 'warning');
        selectService.focus();
        return;
      }

      if (!inputDate.value) {
        showMessage('⚠️ Por favor, selecione uma data!', 'warning');
        inputDate.focus();
        return;
      }

      if (!inputTime.value) {
        showMessage('⚠️ Por favor, selecione um horário!', 'warning');
        inputTime.focus();
        return;
      }

      // Validar se a data/hora não é no passado
      const selectedDateTime = new Date(inputDate.value + ' ' + inputTime.value);
      const now = new Date();

      if (selectedDateTime < now) {
        showMessage('⚠️ Não é possível agendar em horários passados!', 'warning');
        return;
      }

      // Desabilitar botão durante o envio
      const submitBtn = formAgenda.querySelector('button[type="submit"]');
      const originalText = submitBtn.textContent;
      submitBtn.disabled = true;
      submitBtn.textContent = 'Agendando...';

      fetch('add_from_calendar.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `service_id=${selectService.value}&date=${inputDate.value}&time=${inputTime.value}&end_time=${inputEndTime.value}`
      })
        .then(res => res.json())
        .then(resp => {
          if (resp.status === 'success') {
            showMessage('✅ Agendamento realizado com sucesso!', 'success');
            calendar.refetchEvents();
            modal.style.display = 'none';
            formAgenda.reset();
          } else {
            showMessage('❌ ' + (resp.msg || 'Erro ao realizar agendamento'), 'error');
          }
        })
        .catch(err => {
          console.error('Erro:', err);
          showMessage('❌ Erro ao realizar agendamento. Tente novamente.', 'error');
        })
        .finally(() => {
          submitBtn.disabled = false;
          submitBtn.textContent = originalText;
        });
    });
  }
});