document.addEventListener('DOMContentLoaded', function () {
  let calendarEl = document.getElementById('calendar');
  let modal = document.getElementById('modal-agenda');
  let closeModal = modal ? modal.querySelector('.close') : null;
  let formAgenda = document.getElementById('form-agenda');
  let selectService = document.getElementById('agenda-service');
  let inputDate = document.getElementById('agenda-date');
  let inputTime = document.getElementById('agenda-time');
  let inputEndTime = document.getElementById('agenda-end-time');
  let inputClientNotes = document.getElementById('agenda-client-notes');
  let csrfToken = '';

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
    }, 4000);
  }

  // Obter CSRF Token
  function getCSRFToken() {
    return fetch('get_csrf_token.php')
      .then(res => res.json())
      .then(data => {
        csrfToken = data.token;
        return csrfToken;
      })
      .catch(err => {
        console.error('Erro ao obter CSRF token:', err);
        return '';
      });
  }

  // Carregar CSRF Token ao iniciar
  getCSRFToken();

  // Carregar serviços
  if (selectService) {
    selectService.innerHTML = '<option value="">Carregando serviços...</option>';

    fetch('get_services.php')
      .then(res => {
        if (!res.ok) throw new Error('Erro ao carregar serviços');
        return res.json();
      })
      .then(data => {
        const activeServices = data.filter(s => s.active == 1);
        
        if (activeServices.length === 0) {
          selectService.innerHTML = '<option value="">Nenhum serviço disponível</option>';
          showMessage('Nenhum serviço ativo encontrado. Contate o administrador.', 'warning');
        } else {
          selectService.innerHTML = '<option value="">Selecione um serviço</option>' +
            activeServices.map(s => 
              `<option value="${s.id}" data-duration="${s.duration || 60}" data-price="${s.price}">
                ${s.name} - R$ ${parseFloat(s.price).toFixed(2)} (${s.duration}min)
              </option>`
            ).join('');
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
    
    // Validação adicional ao mudar
    inputDate.addEventListener('change', function() {
      if (this.value < today) {
        showMessage('Não é possível agendar em datas passadas', 'warning');
        this.value = today;
      }
    });
  }

  // Verificar horários ocupados ao selecionar data
  if (inputDate && inputTime) {
    inputDate.addEventListener('change', async function() {
      const date = this.value;
      if (!date) return;

      try {
        const res = await fetch(`get_busy_slots.php?date=${date}`);
        const busySlots = await res.json();
        
        if (busySlots.length > 0) {
          console.log('Horários ocupados:', busySlots);
          // Aqui você pode desabilitar horários ou mostrar aviso
        }
      } catch (err) {
        console.error('Erro ao buscar horários ocupados:', err);
      }
    });
  }

  // Verificar se FullCalendar está carregado
  if (typeof FullCalendar === 'undefined') {
    console.error('FullCalendar não foi carregado! Verifique se o script está no HTML.');
    alert('Erro: Biblioteca do calendário não carregada. Recarregue a página.');
    return;
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
    businessHours: {
      daysOfWeek: [1, 2, 3, 4, 5, 6], // Segunda a Sábado
      startTime: '08:00',
      endTime: '20:00'
    },
    slotMinTime: '08:00',
    slotMaxTime: '20:00',
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
      if (status === 'concluido') return ['event-concluido'];
      if (status === 'pendente') return ['event-pendente'];
      if (status === 'cancelado') return ['event-cancelado'];
      if (status === 'bloqueado') return ['event-bloqueado'];
      return ['event-agendado'];
    },
    eventTimeFormat: {
      hour: '2-digit',
      minute: '2-digit',
      hour12: false
    },
    select: function (info) {
      // Verificar se é dono - dono não pode agendar pelo calendário
      fetch('check_role.php')
        .then(res => res.json())
        .then(userData => {
          if (userData.role === 'dono') {
            showMessage('ℹ️ Como dono, você gerencia os agendamentos dos clientes.', 'info');
            calendar.unselect();
            return;
          }

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

            // Limpar seleção de serviço e notas
            if (selectService) selectService.value = '';
            if (inputClientNotes) inputClientNotes.value = '';

            modal.style.display = 'block';
          }
          calendar.unselect();
        })
        .catch(err => {
          console.error('Erro ao verificar role:', err);
          calendar.unselect();
        });
    },
    eventClick: function (info) {
      const eventStatus = info.event.extendedProps.status || 'agendado';
      const eventId = info.event.id;
      const eventTitle = info.event.title;

      // Verificar se usuário é dono ou cliente
      fetch('check_role.php')
        .then(res => res.json())
        .then(userData => {
          if (userData.role === 'dono') {
            // DONO: Pode confirmar ou cancelar agendamentos pendentes
            if (eventStatus === 'pendente') {
              mostrarModalDonoConfirmar(eventId, eventTitle);
            } else if (eventStatus === 'agendado') {
              // Agendamento já confirmado, pode marcar como concluído
              if (confirm(`Marcar "${eventTitle}" como concluído?`)) {
                atualizarStatus(eventId, 'concluido');
              }
            } else if (eventStatus === 'concluido') {
              showMessage('Este agendamento já foi concluído', 'info');
            } else if (eventStatus === 'cancelado') {
              showMessage('Este agendamento foi cancelado', 'info');
            } else if (eventStatus === 'bloqueado') {
              showMessage('Horário bloqueado', 'info');
            }
          } else {
            // CLIENTE: Pode visualizar e cancelar próprios agendamentos
            if (eventStatus === 'pendente' || eventStatus === 'agendado') {
              mostrarModalClienteCancelar(eventId, eventTitle, eventStatus);
            } else if (eventStatus === 'concluido') {
              showMessage('✅ Este agendamento foi concluído.', 'info');
            } else if (eventStatus === 'cancelado') {
              showMessage('❌ Este agendamento foi cancelado.', 'warning');
            }
          }
        })
        .catch(err => {
          console.error('Erro ao verificar role:', err);
          showMessage('Erro ao processar ação', 'error');
        });
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
    formAgenda.addEventListener('submit', async function (e) {
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

      // Obter token CSRF atualizado
      if (!csrfToken) {
        await getCSRFToken();
      }

      // Desabilitar botão durante o envio
      const submitBtn = formAgenda.querySelector('button[type="submit"]');
      const originalText = submitBtn.textContent;
      submitBtn.disabled = true;
      submitBtn.textContent = 'Agendando...';

      const formData = new URLSearchParams();
      formData.append('service_id', selectService.value);
      formData.append('date', inputDate.value);
      formData.append('time', inputTime.value);
      formData.append('end_time', inputEndTime.value);
      formData.append('client_notes', inputClientNotes ? inputClientNotes.value : '');
      formData.append('csrf_token', csrfToken);

      fetch('add_from_calendar.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: formData.toString()
      })
        .then(res => res.json())
        .then(resp => {
          if (resp.status === 'success') {
            showMessage('✅ ' + resp.msg, 'success');
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

  // ========== FUNÇÕES DE ATUALIZAÇÃO DE STATUS ==========
  async function atualizarStatus(eventId, novoStatus, motivo = '') {
    if (!csrfToken) {
      await getCSRFToken();
    }

    const formData = new URLSearchParams();
    formData.append('id', eventId);
    formData.append('status', novoStatus);
    formData.append('motivo', motivo);
    formData.append('csrf_token', csrfToken);

    return fetch('update_status.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: formData.toString()
    })
      .then(res => res.json())
      .then(data => {
        if (data.status === 'success') {
          calendar.refetchEvents();
          
          const mensagens = {
            'agendado': '✅ Agendamento confirmado com sucesso!',
            'cancelado': '❌ Agendamento cancelado',
            'concluido': '✅ Agendamento concluído!'
          };
          
          showMessage(mensagens[novoStatus] || 'Status atualizado!', 'success');

          if (data.whatsapp_link) {
            setTimeout(() => {
              if (confirm('Deseja notificar o cliente via WhatsApp?')) {
                window.open(data.whatsapp_link, '_blank');
              }
            }, 500);
          }
        } else {
          showMessage('❌ ' + (data.msg || 'Erro ao atualizar'), 'error');
        }
        
        return data;
      })
      .catch(err => {
        console.error('Erro:', err);
        showMessage('❌ Erro ao atualizar status', 'error');
        throw err;
      });
  }

  // ========== FUNÇÕES DE MODAL - DONO ==========
  function mostrarModalDonoConfirmar(eventId, eventTitle) {
    const modalHTML = `
      <div id="modal-confirmar" class="modal" style="display: block;">
        <div class="modal-content">
          <span class="close" onclick="fecharModalConfirmar()">&times;</span>
          <h2>🤔 Confirmar ou Recusar Agendamento</h2>
          <p><strong>Agendamento:</strong> ${eventTitle}</p>
          
          <div style="margin: 20px 0;">
            <label for="motivo-recusar">Motivo (opcional ao recusar):</label>
            <textarea id="motivo-recusar" class="input" rows="3" placeholder="Ex: Horário indisponível, cliente solicitou..."></textarea>
          </div>
          
          <div style="display: flex; gap: 10px; margin-top: 20px;">
            <button class="btn btn-primary" onclick="confirmarAgendamento(${eventId})" style="flex: 1; background: linear-gradient(135deg, #4caf50, #2e7d32);">
              ✅ Confirmar
            </button>
            <button class="btn btn-primary" onclick="recusarAgendamento(${eventId})" style="flex: 1; background: linear-gradient(135deg, #f44336, #c62828);">
              ❌ Recusar
            </button>
          </div>
        </div>
      </div>
    `;
    document.body.insertAdjacentHTML('beforeend', modalHTML);
  }

  // ========== FUNÇÕES DE MODAL - CLIENTE ==========
  function mostrarModalClienteCancelar(eventId, eventTitle, status) {
    const statusTexto = status === 'pendente' ? 'pendente de confirmação' : 'confirmado';
    const modalHTML = `
      <div id="modal-cancelar" class="modal" style="display: block;">
        <div class="modal-content">
          <span class="close" onclick="fecharModalCancelar()">&times;</span>
          <h2>❌ Cancelar Agendamento</h2>
          <p><strong>Agendamento:</strong> ${eventTitle}</p>
          <p><strong>Status atual:</strong> ${statusTexto}</p>
          
          <div style="margin: 20px 0;">
            <label for="motivo-cancelar">Motivo do cancelamento (opcional):</label>
            <textarea id="motivo-cancelar" class="input" rows="3" placeholder="Ex: Imprevisto, mudança de planos..."></textarea>
          </div>
          
          <p style="color: #f57c00; margin: 10px 0;">⚠️ Esta ação não pode ser desfeita.</p>
          
          <div style="display: flex; gap: 10px; margin-top: 20px;">
            <button class="btn btn-secondary" onclick="fecharModalCancelar()" style="flex: 1;">
              Voltar
            </button>
            <button class="btn btn-primary" onclick="cancelarMeuAgendamento(${eventId})" style="flex: 1; background: linear-gradient(135deg, #f44336, #c62828);">
              Confirmar Cancelamento
            </button>
          </div>
        </div>
      </div>
    `;
    document.body.insertAdjacentHTML('beforeend', modalHTML);
  }

  // ========== FUNÇÕES GLOBAIS (window) ==========

  window.confirmarAgendamento = async function (eventId) {
    await atualizarStatus(eventId, 'agendado');
    fecharModalConfirmar();
  }

  window.recusarAgendamento = async function (eventId) {
    const motivoElement = document.getElementById('motivo-recusar');
    const motivo = motivoElement ? motivoElement.value.trim() : '';
    await atualizarStatus(eventId, 'cancelado', motivo);
    fecharModalConfirmar();
  }

  window.cancelarMeuAgendamento = async function (eventId) {
    const motivoElement = document.getElementById('motivo-cancelar');
    const motivo = motivoElement ? motivoElement.value.trim() : '';
    await atualizarStatus(eventId, 'cancelado', motivo);
    fecharModalCancelar();
  }

  window.fecharModalConfirmar = function () {
    const modal = document.getElementById('modal-confirmar');
    if (modal) modal.remove();
  }

  window.fecharModalCancelar = function () {
    const modal = document.getElementById('modal-cancelar');
    if (modal) modal.remove();
  }
});
