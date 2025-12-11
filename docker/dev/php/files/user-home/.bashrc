if ! pgrep ssh-agent > /dev/null
then
  eval `ssh-agent`
  rm -f ~/.ssh/ssh_auth_sock
  ln -sf "$SSH_AUTH_SOCK" ~/.ssh/ssh_auth_sock
fi
export SSH_AUTH_SOCK=~/.ssh/ssh_auth_sock
ssh-add -l > /dev/null || ssh-add

if [ -f ~/.bash_aliases ]; then
    . ~/.bash_aliases
fi

# autocomplete for `make` command
complete -W "\`grep -oE '^[a-zA-Z0-9_.-]+:([^=]|$)' ?akefile | sed 's/[^a-zA-Z0-9_.-]*$//'\`" make
