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

            // Limpar seleção de serviço
            if (selectService) selectService.value = '';

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
                const formData = new URLSearchParams();
                formData.append('id', eventId);
                formData.append('status', 'concluido');

                fetch('update_status.php', {
                  method: 'POST',
                  headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                  body: formData.toString()
                })
                  .then(res => res.json())
                  .then(data => {
                    if (data.status === 'success') {
                      calendar.refetchEvents();
                      showMessage('✅ Agendamento concluído!', 'success');

                      if (data.whatsapp_link) {
                        if (confirm('Deseja enviar confirmação via WhatsApp?')) {
                          window.open(data.whatsapp_link, '_blank');
                        }
                      }
                    } else {
                      showMessage('❌ ' + (data.msg || 'Erro ao atualizar'), 'error');
                    }
                  })
                  .catch(err => {
                    console.error('Erro:', err);
                    showMessage('Erro ao atualizar status', 'error');
                  });
              }
            } else if (eventStatus === 'concluido') {
              showMessage('Este agendamento já foi concluído', 'info');
            } else if (eventStatus === 'cancelado') {
              showMessage('Este agendamento foi cancelado', 'info');
            }
          } else {
            // CLIENTE: Pode visualizar e cancelar próprios agendamentos
            if (eventStatus === 'pendente') {
              mostrarModalClienteCancelar(eventId, eventTitle, 'pendente');
            } else if (eventStatus === 'agendado') {
              mostrarModalClienteCancelar(eventId, eventTitle, 'agendado');
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

  window.confirmarAgendamento = function (eventId) {
    console.log('🔵 Confirmando agendamento ID:', eventId);

    const formData = new URLSearchParams();
    formData.append('id', eventId);
    formData.append('status', 'agendado');

    console.log('📤 Enviando dados:', formData.toString());

    fetch('update_status.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: formData.toString()
    })
      .then(res => {
        console.log('📥 Resposta recebida, status:', res.status);
        return res.json();
      })
      .then(data => {
        console.log('✅ Dados recebidos:', data);

        if (data.status === 'success') {
          calendar.refetchEvents();
          fecharModalConfirmar();
          showMessage('✅ Agendamento confirmado com sucesso!', 'success');

          if (data.whatsapp_link) {
            setTimeout(() => {
              if (confirm('✅ Agendamento confirmado!\n\nDeseja enviar confirmação via WhatsApp?')) {
                window.open(data.whatsapp_link, '_blank');
              }
            }, 500);
          }
        } else {
          console.error('❌ Erro na resposta:', data.msg);
          showMessage('❌ ' + (data.msg || 'Erro ao confirmar'), 'error');
        }
      })
      .catch(err => {
        console.error('❌ Erro na requisição:', err);
        showMessage('❌ Erro ao confirmar agendamento', 'error');
      });
  }

  window.recusarAgendamento = function (eventId) {
    console.log('🔴 Recusando agendamento ID:', eventId);

    const motivoElement = document.getElementById('motivo-recusar');
    const motivo = motivoElement ? motivoElement.value.trim() : '';

    const formData = new URLSearchParams();
    formData.append('id', eventId);
    formData.append('status', 'cancelado');
    formData.append('motivo', motivo);

    console.log('📤 Enviando recusa:', formData.toString());

    fetch('update_status.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: formData.toString()
    })
      .then(res => res.json())
      .then(data => {
        console.log('📥 Resposta recusa:', data);

        if (data.status === 'success') {
          calendar.refetchEvents();
          fecharModalConfirmar();
          showMessage('❌ Agendamento recusado', 'warning');

          if (data.whatsapp_link) {
            setTimeout(() => {
              if (confirm('Deseja notificar cliente via WhatsApp?')) {
                window.open(data.whatsapp_link, '_blank');
              }
            }, 500);
          }
        } else {
          console.error('❌ Erro ao recusar:', data.msg);
          showMessage('❌ ' + (data.msg || 'Erro ao recusar'), 'error');
        }
      })
      .catch(err => {
        console.error('❌ Erro na requisição:', err);
        showMessage('❌ Erro ao recusar agendamento', 'error');
      });
  }

  window.cancelarMeuAgendamento = function (eventId) {
    console.log('🟡 Cliente cancelando ID:', eventId);

    const motivoElement = document.getElementById('motivo-cancelar');
    const motivo = motivoElement ? motivoElement.value.trim() : '';

    const formData = new URLSearchParams();
    formData.append('id', eventId);
    formData.append('status', 'cancelado');
    formData.append('motivo', motivo);

    console.log('📤 Enviando cancelamento:', formData.toString());

    fetch('update_status.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: formData.toString()
    })
      .then(res => res.json())
      .then(data => {
        console.log('📥 Resposta cancelamento:', data);

        if (data.status === 'success') {
          calendar.refetchEvents();
          fecharModalCancelar();
          showMessage('✅ Agendamento cancelado com sucesso', 'success');
        } else {
          console.error('❌ Erro ao cancelar:', data.msg);
          showMessage('❌ ' + (data.msg || 'Erro ao cancelar'), 'error');
        }
      })
      .catch(err => {
        console.error('❌ Erro na requisição:', err);
        showMessage('❌ Erro ao cancelar agendamento', 'error');
      });
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