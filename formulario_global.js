// Formata CPF/CNPJ
function formatarCpfCnpj(input) {
    const value = input.value.replace(/\D/g, '');

    if (value.length <= 11) {
        // Formata CPF (000.000.000-00)
        input.value = value.replace(/(\d{3})(\d)/, '$1.$2')
            .replace(/(\d{3})(\d)/, '$1.$2')
            .replace(/(\d{3})(\d{2})$/, '$1-$2');
    } else {
        // Formata CNPJ (00.000.000/0000-00)
        input.value = value.replace(/(\d{2})(\d)/, '$1.$2')
            .replace(/(\d{3})(\d)/, '$1.$2')
            .replace(/(\d{3})(\d)/, '$1/$2')
            .replace(/(\d{4})(\d)/, '$1-$2');
    }
}

// Formata RG (opcional, formato comum: 00.000.000-0)
function formatarRG(input) {
    const value = input.value.replace(/\D/g, '');
    if (value.length <= 8) {
        input.value = value.replace(/(\d{2})(\d)/, '$1.$2')
            .replace(/(\d{3})(\d)/, '$1.$2')
            .replace(/(\d{3})(\d{1})$/, '$1-$2');
    } else {
        // Formato alternativo para RGs maiores
        input.value = value.replace(/(\d{2})(\d)/, '$1.$2')
            .replace(/(\d{3})(\d)/, '$1.$2')
            .replace(/(\d{3})(\d)/, '$1-$2');
    }
}

// Busca endereço via API ViaCEP
function buscarEndereco() {
    const cep = document.getElementById('cep').value.replace(/\D/g, '');

    if (cep.length !== 8) return;

    fetch(`https://viacep.com.br/ws/${cep}/json/`)
        .then(response => response.json())
        .then(data => {
            if (!data.erro) {
                document.getElementById('endereco').value = data.logradouro || '';
                document.getElementById('bairro').value = data.bairro || '';
                // Foca no campo número após preencher
                document.getElementById('numero').focus();
            } else {
                alert('CEP não encontrado');
            }
        })
        .catch(error => {
            console.error('Erro ao buscar CEP:', error);
            alert('Erro ao buscar CEP. Verifique sua conexão.');
        });
}

// Formata Telefone/WhatsApp para números brasileiros e internacionais
function formatarTelefone(input) {
    const value = input.value.replace(/\D/g, '');

    // Se começar com código de país (ex: 55 para Brasil)
    if (value.length > 11) {
        input.value = value.replace(/(\d{2})(\d)/, '+$1 $2')
            .replace(/(\d{2})(\d)/, '$1 $2')
            .replace(/(\d{4,5})(\d{4})$/, '$1-$2');
    }
    // Formato brasileiro padrão
    else if (value.length > 2) {
        input.value = value.replace(/(\d{2})(\d)/, '($1) $2')
            .replace(/(\d{4,5})(\d{4})$/, '$1-$2');
    }
}

// Formata CEP (xxxxx-xxx)
function formatarCep(input) {
    const value = input.value.replace(/\D/g, '');
    if (value.length > 5) {
        input.value = value.replace(/(\d{5})(\d)/, '$1-$2');
    } else {
        input.value = value;
    }
}

// Adicionando os eventos
document.addEventListener('DOMContentLoaded', function () {
    // CPF
    document.getElementById('cpf').addEventListener('input', function (event) {
        formatarCpfCnpj(this);
    });

    // RG
    document.getElementById('rg').addEventListener('input', function (event) {
        formatarRG(this);
    });

    // Telefone
    document.getElementById('telefone').addEventListener('input', function (event) {
        formatarTelefone(this);
    });

    // WhatsApp
    document.getElementById('whatsapp').addEventListener('input', function (event) {
        formatarTelefone(this);
    });

    // CEP
    document.getElementById('cep').addEventListener('input', function (event) {
        formatarCep(this);
    });

    // Auto-completar endereço quando sair do campo CEP
    document.getElementById('cep').addEventListener('blur', buscarEndereco);
});
